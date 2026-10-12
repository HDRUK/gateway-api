const http = require('node:http');
const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');

const port = Number(process.env.MOCK_OA_PORT || 8081);
const issuer = process.env.MOCK_OA_ISSUER || `http://localhost:${port}`;
const expectedClientId = process.env.MOCK_OA_CLIENT_ID || 'gatewaylocal';
const expectedClientSecret = process.env.MOCK_OA_CLIENT_SECRET || 'gatewaylocalsecret';

const profiles = JSON.parse(
  fs.readFileSync(path.join(__dirname, 'profiles.json'), 'utf8')
);

const keyId = 'mock-openathens';
const signingKey = crypto.generateKeyPairSync('rsa', { modulusLength: 2048 });
const unpublishedKey = crypto.generateKeyPairSync('rsa', { modulusLength: 2048 });

const issuedCodes = new Map();
const issuedTokens = new Map();

const base64url = input => Buffer.from(input).toString('base64url');
const phpUrlDecode = value => decodeURIComponent(value.replace(/\+/g, '%20'));
const randomId = () => crypto.randomBytes(16).toString('hex');

const log = message =>
  process.stdout.write(`${new Date().toISOString()}  ${message}\n`);

const faultsFrom = value => new Set((value || '').split(',').map(f => f.trim()).filter(Boolean));

function publishedJwks() {
  const jwk = signingKey.publicKey.export({ format: 'jwk' });
  return { keys: [{ ...jwk, kid: keyId, use: 'sig', alg: 'RS256' }] };
}

function signIdToken(profileName, faults) {
  const now = Math.floor(Date.now() / 1000);

  const header = {
    alg: 'RS256',
    typ: 'JWT',
    kid: faults.has('kid') ? 'a-key-not-in-the-jwks' : keyId,
  };

  const payload = {
    iss: faults.has('iss') ? 'https://wrong-issuer.example' : issuer,
    aud: faults.has('aud') ? 'a-different-client' : expectedClientId,
    sub: `mock-${profileName}`,
    iat: now,
    exp: faults.has('exp') ? now - 3600 : now + 300,
  };

  if (faults.has('nbf')) {
    payload.nbf = now + 3600;
  }

  if (faults.has('nonce')) {
    payload.nonce = 'a-nonce-the-client-never-sent';
  }

  const signingInput =
    `${base64url(JSON.stringify(header))}.${base64url(JSON.stringify(payload))}`;

  const key = faults.has('kid') ? unpublishedKey.privateKey : signingKey.privateKey;
  let signature = crypto
    .sign('sha256', Buffer.from(signingInput), key)
    .toString('base64url');

  if (faults.has('sig')) {
    signature = `${signature.slice(0, -4)}AAAA`;
  }

  return `${signingInput}.${signature}`;
}

function send(res, status, contentType, body) {
  res.writeHead(status, { 'Content-Type': contentType });
  res.end(body);
}

const sendJson = (res, status, payload) =>
  send(res, status, 'application/json', JSON.stringify(payload, null, 2));

const escapeHtml = value =>
  String(value).replace(/[&<>"]/g, c =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])
  );

function page(title, body) {
  return `<!doctype html><meta charset="utf-8"><title>${title}</title>
<style>body{font:15px/1.6 system-ui,sans-serif;max-width:46rem;margin:3rem auto;padding:0 1rem}
code{background:#eee;padding:.1em .3em;border-radius:3px}li{margin:.35rem 0}
label{display:block;margin:.6rem 0}.bad{color:#a00}</style><h1>${title}</h1>${body}`;
}

const carriedParams = ['client_id', 'redirect_uri', 'response_type', 'scope', 'state'];

function renderPicker(params) {
  const options = Object.entries(profiles)
    .map(([name, profile]) =>
      `<option value="${escapeHtml(name)}">${escapeHtml(name)} — ` +
      `${escapeHtml(profile.label)}</option>`)
    .join('\n');

  const hidden = carriedParams
    .filter(key => params[key] !== undefined)
    .map(key =>
      `<input type="hidden" name="${key}" value="${escapeHtml(params[key])}">`)
    .join('\n');

  return page(
    'Mock OpenAthens — choose an identity',
    `<p>Signing in to <code>${escapeHtml(params.client_id)}</code>,
        returning to <code>${escapeHtml(params.redirect_uri)}</code>.</p>
     <form method="get" action="/oidc/auth">
       ${hidden}
       <p><label>Identity
          <select name="profile">${options}</select></label></p>
       <p><label>Inject faults, comma-separated
          <input name="break" size="48"
                 placeholder="iss,aud,exp,nbf,nonce,sig,kid,userinfo,laxredirect">
          </label></p>
       <p><button type="submit">Sign in</button></p>
     </form>`
  );
}

function handleAuthorize(url, res) {
  const params = Object.fromEntries(url.searchParams);

  if (params.client_id !== expectedClientId) {
    log(`  rejected: unknown client_id ${params.client_id || '(none)'}, expected ${expectedClientId}`);
    return send(res, 400, 'text/html',
      page('Unknown client',
        `<p class="bad">This IdP does not know the client
           <code>${escapeHtml(params.client_id || '(none)')}</code>.</p>
         <p>It expects <code>${escapeHtml(expectedClientId)}</code>.
            Check <code>OPENATHENS_CLIENT_ID</code>.</p>`));
  }

  if (params.response_type !== 'code') {
    return send(res, 400, 'text/html',
      page('Unsupported response_type',
        `<p class="bad">Only <code>code</code> is supported.</p>`));
  }

  if (!params.redirect_uri) {
    return send(res, 400, 'text/html',
      page('Missing redirect_uri', `<p class="bad">No redirect_uri given.</p>`));
  }

  if (!params.profile) {
    return send(res, 200, 'text/html', renderPicker(params));
  }

  if (!profiles[params.profile]) {
    return send(res, 400, 'text/html',
      page('Unknown profile',
        `<p class="bad">No profile named
           <code>${escapeHtml(params.profile)}</code>.</p>`));
  }

  const faults = faultsFrom(params.break);
  const code = randomId();
  issuedCodes.set(code, {
    profileName: params.profile,
    redirectUri: params.redirect_uri,
    faults,
  });

  log(`  issued code for profile=${params.profile} faults=[${[...faults].join(',') || 'none'}]`);

  const back = new URL(params.redirect_uri);
  back.searchParams.set('code', code);
  if (params.state !== undefined) {
    back.searchParams.set('state', params.state);
  }

  res.writeHead(302, { Location: back.toString() });
  res.end();
}

function clientCredentials(req, body) {
  const header = req.headers.authorization || '';

  if (header.startsWith('Basic ')) {
    const decoded = Buffer.from(header.slice(6), 'base64').toString('utf8');
    const separator = decoded.indexOf(':');
    return {
      id: phpUrlDecode(decoded.slice(0, separator)),
      secret: phpUrlDecode(decoded.slice(separator + 1)),
    };
  }

  return { id: body.get('client_id'), secret: body.get('client_secret') };
}

function handleToken(req, res, rawBody) {
  const body = new URLSearchParams(rawBody);
  const { id, secret } = clientCredentials(req, body);

  if (id !== expectedClientId || secret !== expectedClientSecret) {
    log(`  rejected: bad client credentials for ${id || '(none)'}`);
    return sendJson(res, 401, {
      error: 'invalid_client',
      error_description: 'client id or secret does not match this IdP',
    });
  }

  if (body.get('grant_type') !== 'authorization_code') {
    return sendJson(res, 400, { error: 'unsupported_grant_type' });
  }

  const code = body.get('code');
  const issued = issuedCodes.get(code);
  issuedCodes.delete(code);

  if (!issued) {
    log('  rejected: authorization code unknown or already redeemed');
    return sendJson(res, 400, {
      error: 'invalid_grant',
      error_description: 'authorization code is unknown or already redeemed',
    });
  }

  const presented = body.get('redirect_uri');
  if (!issued.faults.has('laxredirect') && presented !== issued.redirectUri) {
    log(`  rejected: redirect_uri mismatch (authorize ${issued.redirectUri}, token ${presented})`);
    return sendJson(res, 400, {
      error: 'invalid_grant',
      error_description:
        `redirect_uri mismatch: authorize used ${issued.redirectUri}, ` +
        `token request used ${presented}`,
    });
  }

  const accessToken = randomId();
  issuedTokens.set(accessToken, issued);

  log(`  issued id_token for profile=${issued.profileName}`);

  return sendJson(res, 200, {
    access_token: accessToken,
    token_type: 'Bearer',
    expires_in: 300,
    id_token: signIdToken(issued.profileName, issued.faults),
  });
}

function handleUserinfo(req, res) {
  const header = req.headers.authorization || '';
  const issued = issuedTokens.get(header.replace(/^Bearer /, ''));

  if (!issued) {
    log('  rejected: unknown access token at userinfo');
    return sendJson(res, 401, { error: 'invalid_token' });
  }

  if (issued.faults.has('userinfo')) {
    log('  returning HTTP 500 from userinfo (fault)');
    return sendJson(res, 500, { error: 'server_error' });
  }

  log(`  returned claims for profile=${issued.profileName}`);

  return sendJson(res, 200, {
    sub: `mock-${issued.profileName}`,
    ...profiles[issued.profileName].claims,
  });
}

function handleIndex(res) {
  const items = Object.entries(profiles)
    .map(([name, profile]) =>
      `<li><code>${escapeHtml(name)}</code> — ${escapeHtml(profile.label)}</li>`)
    .join('\n');

  return send(res, 200, 'text/html', page('Mock OpenAthens',
    `<p>Issuer <code>${escapeHtml(issuer)}</code>,
        client <code>${escapeHtml(expectedClientId)}</code>.</p>
     <p>Endpoints: <code>/oidc/auth</code>, <code>/oidc/token</code>,
        <code>/oidc/jwks</code>, <code>/oidc/userinfo</code>.</p>
     <h2>Profiles</h2><ul>${items}</ul>`));
}

http
  .createServer((req, res) => {
    const url = new URL(req.url, issuer);
    log(`${req.method} ${url.pathname}`);

    if (req.method === 'GET' && url.pathname === '/') {
      return handleIndex(res);
    }
    if (req.method === 'GET' && url.pathname === '/oidc/auth') {
      return handleAuthorize(url, res);
    }
    if (req.method === 'GET' && url.pathname === '/oidc/jwks') {
      return sendJson(res, 200, publishedJwks());
    }
    if (req.method === 'GET' && url.pathname === '/oidc/userinfo') {
      return handleUserinfo(req, res);
    }
    if (req.method === 'POST' && url.pathname === '/oidc/token') {
      const chunks = [];
      req.on('data', chunk => chunks.push(chunk));
      req.on('end', () =>
        handleToken(req, res, Buffer.concat(chunks).toString('utf8')));
      return undefined;
    }

    log(`  no route for ${req.method} ${url.pathname}`);
    return sendJson(res, 404, { error: 'not_found', path: url.pathname });
  })
  .listen(port, () => {
    process.stdout.write(`mock OpenAthens listening on ${issuer}\n`);
  });

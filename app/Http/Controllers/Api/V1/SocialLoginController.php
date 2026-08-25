<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\JwtController;
use Laravel\Socialite\Facades\Socialite;
use Jumbojett\OpenIDConnectClient;

class SocialLoginController extends Controller
{
    /**
     * Jwt Constroller
     *
     * @var JwtController
     */
    private JwtController $jwt;

    /**
     * Constructor
     *
     * @param JwtController $jwt
     */
    public function __construct(JwtController $jwt)
    {
        $this->jwt = $jwt;
    }

    /**
    * @OA\Get(
    *    path="/api/v1/auth/dta/{provider}",
    *    operationId="dta-login",
    *    tags={"Authentication"},
    *    summary="SocialLoginController@dtaLogin",
    *    description="Login with Google / Linkedin with OpenId / Azure",
    *    @OA\Parameter(
    *       name="provider",
    *       in="path",
    *       description="google, linkedin, azure",
    *       required=true,
    *       example="google",
    *       @OA\Schema(
    *          type="string",
    *          description="provider",
    *       ),
    *    ),
    *    @OA\Parameter(
    *       name="redirect",
    *       in="redirect",
    *       description="url to redirect to",
    *       required=true,
    *       example="1",
    *       @OA\Schema(
    *          type="string",
    *          description="redirect",
    *       ),
    *    ),
    *    @OA\Response(
    *       response=302,
    *       description="redirect to main page",
    *    ),
    *    @OA\Response(
    *       response=401,
    *       description="Unauthorized",
    *    ),
    * )
    *
    * redirect to google authorization page
    *
    * @param Request $request
    * @param string $provider
    * @return mixed
    */
    public function dtaLogin(Request $request, string $provider): mixed
    {
        return $this->handleLogin($request, $provider, config('services.dta.url'), config('services.openathens.dta_redirect'), true);
    }


    /**
     * @OA\Get(
     *    path="/api/v1/auth/{provider}",
     *    operationId="login",
     *    tags={"Authentication"},
     *    summary="SocialLoginController@login",
     *    description="Login with Google / Linkedin with OpenId / Azure",
     *    @OA\Parameter(
     *       name="provider",
     *       in="path",
     *       description="google, linkedin, azure",
     *       required=true,
     *       example="google",
     *       @OA\Schema(
     *          type="string",
     *          description="provider",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="redirect",
     *       in="redirect",
     *       description="url to redirect to",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="string",
     *          description="redirect",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=302,
     *       description="redirect to main page",
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *    ),
     * )
     *
     * redirect to google authorization page
     *
     * @param Request $request
     * @param string $provider
     * @return mixed
     */
    public function login(Request $request, string $provider): mixed
    {
        return $this->handleLogin($request, $provider, config('gateway.gateway_url'), config('services.openathens.redirect'), false);

    }

    private function handleLogin(Request $request, string $provider, string $baseRedirectUrl, $openAthensRedirectUrl, $isDTA): mixed
    {

        $redirectUrl = $baseRedirectUrl;
        if ($request->has("redirect")) {

            $redirectUrl .= $request->query('redirect');
        }

        session(['redirectUrl' => $redirectUrl]);

        if (strtolower($provider) === 'registry') {
            return $this->registryLoginRedirect();
        }

        if (strtolower($provider) === 'openathens') {
            $provider = 'open-athens';

            if ($request->has('target_link_uri')) {
                session(['redirectUrl' => $request->query('target_link_uri')]);
            }

            $params = [
                'client_id' => Config::get('services.openathens.client_id'),
                'redirect_uri' => $openAthensRedirectUrl,
                'response_type' => 'code',
                'scope' => 'openid',
                'state' => bin2hex(random_bytes(16))
            ];
            $oaUrl = config('services.openathens.issuer') . '/oidc/auth?' . http_build_query($params);

            return redirect()->away($oaUrl);
        } else {
            if (strtolower($provider) === 'linkedin') {
                $provider = 'linkedin-openid';
            }
            if ($isDTA) {
                $providerURL = config("services.$provider.redirect");
                if (config('app.env') !== 'local') {
                    $providerURL = str_replace(config('app.url').'/api/v1/auth', config('services.dta.api_url').'/api/v1/auth/dta', $providerURL);
                }

                return Socialite::driver($provider)
                ->with(['redirect_uri' => $providerURL])
                ->redirect();

            }


            return Socialite::driver($provider)->redirect();

        }
    }


    /**
         * @OA\Get(
         *    path="/api/v1/auth/dta/{provider}/callback",
         *    operationId="dta-login-callback",
         *    tags={"Authentication"},
         *    summary="SocialLoginController@dtaCallback",
         *    description="Login with Google / Linkedin with OpenId / Azure",
         *    @OA\Parameter(
         *       name="provider",
         *       in="path",
         *       description="google, linkedin with openid, azure",
         *       required=true,
         *       example="google",
         *       @OA\Schema(
         *          type="string",
         *          description="provider",
         *       ),
         *    ),
         *    @OA\Response(
         *       response=302,
         *       description="redirect to main page",
         *    ),
         *    @OA\Response(
         *       response=401,
         *       description="Unauthorized",
         *    ),
         * )
         *
         *
         * redirect to front end page with token
         *
         * @param Request $request
         * @param string $provider
         * @return mixed
         */
    public function dtaCallback(Request $request, string $provider): mixed
    {
        $openAthensRedirectUrl = config('services.openathens.redirect');

        $user = null;
        try {
            if (strtolower($provider) === 'linkedin') {
                $provider = 'linkedin-openid';
            }
            if (strtolower($provider) === 'openathens') {
                $provider = 'open-athens';
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $input = $request->all();
                $code = array_key_exists('code', $input) ? $input['code'] : '';
                $_REQUEST['code'] = $code;
                $state = array_key_exists('state', $input) ? $input['state'] : '';
                $_REQUEST['state'] = $state;
                $_SESSION['openid_connect_state'] = $state;

                $oidc = new OpenIDConnectClient(
                    Config::get('services.openathens.issuer'),
                    Config::get('services.openathens.client_id'),
                    Config::get('services.openathens.client_secret')
                );
                $oidc->providerConfigParam([
                    'authorization_endpoint' => config('services.openathens.issuer') . '/oidc/auth',
                    'jwks_uri' => config('services.openathens.issuer') . '/oidc/jwks',
                    'token_endpoint' => config('services.openathens.issuer') . '/oidc/token',
                    'userinfo_endpoint' => config('services.openathens.issuer') . '/oidc/userinfo',
                ]);

                $oidc->setRedirectUrl($openAthensRedirectUrl);
                $oidc->authenticate();

                $response = $oidc->requestUserInfo();
                $socialUser = json_decode(json_encode($response), true);
                $socialUserDetails = $this->openathensResponse($socialUser, $provider);

                $user = User::where('providerid', $socialUserDetails['providerid'])->first();
            } else {
                $providerURL = config("services.$provider.redirect");
                if (config('app.env') !== 'local') {
                    $providerURL = str_replace(config('app.url').'/api/v1/auth', config('services.dta.api_url').'/api/v1/auth/dta', $providerURL);
                }                $socialUser = Socialite::driver($provider)
                ->with(['redirect_uri' => $providerURL])
                ->stateless()
                ->user();

                $socialUserDetails = [];
                switch (strtolower($provider)) {
                    case 'google':
                        $socialUserDetails = $this->googleResponse($socialUser, $provider);
                        break;

                    case 'linkedin-openid':
                        $socialUserDetails = $this->linkedinOpenIdResponse($socialUser, $provider);
                        break;
                    case 'azure':
                        $socialUserDetails = $this->azureResponse($socialUser, $provider);
                        break;
                }
                $user = User::where('email', $socialUserDetails['email'])->first();
            }

            if (!$user) {
                $user = $this->saveUser($socialUserDetails, $provider);
            } else {
                $user = $this->updateUser($user, $socialUserDetails, $provider);
            }

            $jwt = $this->createJwt($user);
            Auditor::log([
                'target_user_id' => $user->id,
                'action_type' => 'LOGIN',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'User ' . $user->id . ' with login through ' . $user->provider . ' has been connected',
            ]);

            $cookies = [Cookie::make('token', $jwt)];

            if ($user['name'] === '' || $user['email'] === '') {
                return redirect()->away(config('services.dta.url') . '/account/profile')->withCookies($cookies);
            } else {
                $redirectUrl = session('redirectUrl');
                return redirect()->away($redirectUrl ?? config('services.dta.url'))->withCookies($cookies);
            }
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/auth/{provider}/callback",
     *    operationId="login-callback",
     *    tags={"Authentication"},
     *    summary="SocialLoginController@callback",
     *    description="Login with Google / Linkedin with OpenId / Azure",
     *    @OA\Parameter(
     *       name="provider",
     *       in="path",
     *       description="google, linkedin with openid, azure",
     *       required=true,
     *       example="google",
     *       @OA\Schema(
     *          type="string",
     *          description="provider",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=302,
     *       description="redirect to main page",
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *    ),
     * )
     *
     *
     * redirect to front end page with token
     *
     * @param Request $request
     * @param string $provider
     * @return mixed
     */
    public function callback(Request $request, string $provider): mixed
    {
        $baseRedirectUrl = config('gateway.gateway_url');
        $openAthensRedirectUrl =  config('services.openathens.redirect');
        $user = null;
        try {
            if (strtolower($provider) === 'linkedin') {
                $provider = 'linkedin-openid';
            }
            if (strtolower($provider) === 'registry') {
                $socialUserDetails = $this->redeemRegistryHandoff($request);

                $user = User::where('providerid', $socialUserDetails['providerid'])->first();
            } elseif (strtolower($provider) === 'openathens') {
                $provider = 'open-athens';
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $input = $request->all();
                $code = array_key_exists('code', $input) ? $input['code'] : '';
                $_REQUEST['code'] = $code;
                $state = array_key_exists('state', $input) ? $input['state'] : '';
                $_REQUEST['state'] = $state;
                $_SESSION['openid_connect_state'] = $state;

                $oidc = new OpenIDConnectClient(
                    Config::get('services.openathens.issuer'),
                    Config::get('services.openathens.client_id'),
                    Config::get('services.openathens.client_secret')
                );
                $oidc->providerConfigParam([
                    'authorization_endpoint' => config('services.openathens.issuer') . '/oidc/auth',
                    'jwks_uri' => config('services.openathens.issuer') . '/oidc/jwks',
                    'token_endpoint' => config('services.openathens.issuer') . '/oidc/token',
                    'userinfo_endpoint' => config('services.openathens.issuer') . '/oidc/userinfo',
                ]);

                $oidc->setRedirectUrl($openAthensRedirectUrl);
                $oidc->authenticate();

                $response = $oidc->requestUserInfo();
                $socialUser = json_decode(json_encode($response), true);
                $socialUserDetails = $this->openathensResponse($socialUser, $provider);

                $user = User::where('providerid', $socialUserDetails['providerid'])->first();
            } else {
                $socialUser = Socialite::driver($provider)->user();

                $socialUserDetails = [];
                switch (strtolower($provider)) {
                    case 'google':
                        $socialUserDetails = $this->googleResponse($socialUser, $provider);
                        break;

                    case 'linkedin-openid':
                        $socialUserDetails = $this->linkedinOpenIdResponse($socialUser, $provider);
                        break;
                    case 'azure':
                        $socialUserDetails = $this->azureResponse($socialUser, $provider);
                        break;
                }
                $user = User::where('email', $socialUserDetails['email'])->first();
            }

            if (!$user) {
                $user = $this->saveUser($socialUserDetails, $provider);
            } else {
                $user = $this->updateUser($user, $socialUserDetails, $provider);
            }

            $jwt = $this->createJwt($user);

            Auditor::log([
                'target_user_id' => $user->id,
                'action_type' => 'LOGIN',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'User ' . $user->id . ' with login through ' . $user->provider . ' has been connected',
            ]);

            $cookies = [Cookie::make('token', $jwt)];

            if ($user['name'] === '' || $user['email'] === '') {
                return redirect()->away($baseRedirectUrl . '/account/profile')->withCookies($cookies);
            } else {
                $redirectUrl = session('redirectUrl');
                return redirect()->away($redirectUrl)->withCookies($cookies);
            }
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Uniform response from Google
     *
     * @param object $data
     * @param string $provider
     * @return array
     */
    private function googleResponse(object $data, string $provider): array
    {
        return [
            'providerid' => $data->getId(),
            'name' => $data->getName(),
            'firstname' => $data->user['given_name'] ?? '',
            'lastname' => $data->user['family_name'] ?? '',
            'email' => $data->getEmail(),
            'provider' => $provider,
            'password' => Hash::make(json_encode($data)),
        ];
    }

    /**
     * Uniform response from LinkedIn using OpenID Connect
     *
     * @param object $data
     * @param string $provider
     * @return array
     */
    private function linkedinOpenIdResponse(object $data, string $provider): array
    {
        return [
            'providerid' => (string)$data->getId(),
            'name' => (string)$data->getName(),
            'firstname' => (string)$data->user['given_name'],
            'lastname' => (string)$data->user['family_name'],
            'email' => (string)$data->getEmail(),
            'provider' => $provider,
            'password' => Hash::make(json_encode($data)),
        ];
    }

    /**
     * Uniform response from Azure
     *
     * @param object $data
     * @param string $provider
     * @return array
     */
    private function azureResponse(object $data, string $provider): array
    {
        $emailAddress = $data['mail'] ? $data['mail'] : $data->getEmail();
        return [
            'providerid' => $data->getId(),
            'name' => $data->getName(),
            'firstname' => $data->offsetGet('givenName'),
            'lastname' => $data->offsetGet('surname'),
            'email' => $emailAddress,
            'provider' => $provider,
            'password' => Hash::make(json_encode($data)),
        ];
    }

    /**
     * Uniform response from OpenAthens
     *
     * @param array $data
     * @param string $provider
     * @return array
     */
    private function openathensResponse(array $data, string $provider): array
    {
        $targetedId = is_array($data['eduPersonTargetedID']) ? $data['eduPersonTargetedID'][0] : $data['eduPersonTargetedID'];
        $affiliation = is_array($data['eduPersonScopedAffiliation']) ? $data['eduPersonScopedAffiliation'][0] : $data['eduPersonScopedAffiliation'];
        return [
            'providerid' => $targetedId,
            'name' => '',
            'firstname' => '',
            'lastname' => '',
            'email' => $targetedId . $affiliation,
            'provider' => $provider,
            'password' => Hash::make(json_encode($data)),
        ];
    }

    /**
     * Redirect the browser to Safe People Registry's own sign-in entry point.
     * Registry never sends Keycloak's auth code to Gateway directly - it's
     * bound to Registry's own redirect_uri - so Gateway's callback URL is
     * passed through as `external_redirect` and Registry hands back a
     * short-lived handoff code of its own once it has completed the exchange.
     *
     * @return mixed
     */
    private function registryLoginRedirect(): mixed
    {
        $callbackUrl = config('app.url') . '/api/v1/auth/registry/callback';

        $registryUrl = rtrim(config('services.registry.web_url'), '/')
            . config('services.registry.login_path')
            . '?' . http_build_query(['external_redirect' => $callbackUrl]);

        return redirect()->away($registryUrl);
    }

    /**
     * Redeem the single-use handoff code minted by Safe People Registry for
     * the authenticated user's claims. This call is server-to-server and
     * HMAC-signed with a secret shared out-of-band with the Registry.
     *
     * @param Request $request
     * @return array
     */
    private function redeemRegistryHandoff(Request $request): array
    {
        $code = $request->query('code');

        if (!$code) {
            throw new Exception('Missing handoff code from Safe People Registry');
        }

        $signature = base64_encode(hash_hmac('sha256', $code, config('services.registry.handoff_secret'), true));

        $response = Http::withHeaders([
            'x-signature' => $signature,
        ])->post(rtrim(config('services.registry.api_url'), '/') . "/auth/gateway_handoff/{$code}/redeem");

        if (!$response->successful()) {
            throw new Exception('Failed to redeem Safe People Registry handoff code');
        }

        return $this->registryResponse($response->json('data') ?? [], 'registry');
    }

    /**
     * Uniform response from Safe People Registry. Identity is matched on
     * the Keycloak `sub` claim (providerid), not email - Registry email
     * changes shouldn't silently merge into an unrelated Gateway account.
     *
     * @param array $data
     * @param string $provider
     * @return array
     */
    private function registryResponse(array $data, string $provider): array
    {
        return [
            'providerid' => $data['sub'] ?? '',
            'name' => trim(($data['given_name'] ?? '') . ' ' . ($data['family_name'] ?? '')),
            'firstname' => $data['given_name'] ?? '',
            'lastname' => $data['family_name'] ?? '',
            'email' => $data['email'] ?? '',
            'provider' => $provider,
            'password' => Hash::make(json_encode($data)),
        ];
    }

    /**
     * update user in database
     *
     * @param User $user
     * @param array $data
     * @param string $provider
     * @return User
     */
    private function updateUser(User $user, array $data, string $provider): User
    {
        if ($provider == 'open-athens') {
            $user->providerid = $data['providerid'];
            $user->preferred_email = 'secondary';
            $user->update();
        } else {
            $user->providerid = $data['providerid'];
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->provider = $data['provider'];
            $user->password = $data['password'];
            $user->update();
        }

        return $user;
    }

    /**
     * save user in database
     *
     * @param array $value
     * @param string $provider
     * @return User
     */
    private function saveUser(array $value, string $provider): User
    {
        $user = new User();
        $user->providerid = $value['providerid'];
        $user->name = $value['name'];
        $user->firstname = $value['firstname'];
        $user->lastname = $value['lastname'];
        $user->email = $value['email'];
        $user->provider = $value['provider'];
        $user->password = $value['password'];
        if ($provider == 'open-athens') {
            $user->preferred_email = 'secondary';
        }
        $user->save();

        return $user;
    }

    /**
     * create JWT token
     *
     * @param User $user
     * @return string
     */
    private function createJwt(User $user): string
    {
        return $this->jwt->generateToken($user->id);
    }
}

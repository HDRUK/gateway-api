<?php

namespace App\Http\Middleware;

use App\Context\GwdmVersionContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes the resolved GWDM version into the Laravel request-scoped Context.
 *
 * Laravel 12 automatically propagates Context entries into any jobs dispatched
 * during the request, so LinkageExtraction, TermExtraction, and other enrichment
 * jobs receive the same gwdm_version value without explicit constructor passing.
 *
 * This middleware is a no-op when no x-gwdm-version header is present:
 * GwdmVersionContext falls back to Config::get('metadata.GWDM.version'),
 * which is the existing system default — no behaviour change for existing callers.
 */
class ResolveGwdmVersionContext
{
    public function __construct(private readonly GwdmVersionContext $gwdmVersionContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        Context::add('gwdm_version', $this->gwdmVersionContext->targetVersion());

        return $next($request);
    }
}

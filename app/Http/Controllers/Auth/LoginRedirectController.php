<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginRedirectController extends Controller
{
    /**
     * Check if the user is in the middle of a login redirect
     */
    public function checkRedirect(Request $request)
    {
        // Check if we have a login redirect flag in session
        $isLoginRedirect = session('is_login_redirect', false) === true;
        
        // Also check if there's an intended URL in the session (Laravel sets this during login)
        $intendedUrl = session('url.intended', null);
        
        // If there's an intended URL and the current URL is the root, we're likely in a login redirect
        if (!$isLoginRedirect && $intendedUrl !== null && $request->path() === '') {
            $isLoginRedirect = true;
        }
        
        return response()->json([
            'is_login_redirect' => $isLoginRedirect,
            'intended_url' => $intendedUrl
        ]);
    }
}

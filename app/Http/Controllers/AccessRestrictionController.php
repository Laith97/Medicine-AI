<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccessRestrictionController extends Controller
{
    /**
     * Show the access restriction page
     */
    public function restricted(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isRestricted()) {
            return redirect()->route('dashboard');
        }

        $restrictionMessage = $request->session()->get('restriction_message', $user->getRestrictionMessage());
        $unpaidInvoices = $user->stripeInvoices()
            ->where('invoice_type', 'monthly')
            ->unpaid()
            ->orderBy('due_date')
            ->get();

        $totalUnpaidAmount = $user->getTotalUnpaidMonthlyAmount();

        return view('access.restricted', compact(
            'restrictionMessage',
            'unpaidInvoices',
            'totalUnpaidAmount'
        ));
    }

    /**
     * AJAX endpoint to check restriction status
     */
    public function checkStatus(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['restricted' => false]);
        }

        return response()->json([
            'restricted' => $user->isRestricted(),
            'message' => $user->getRestrictionMessage(),
            'unpaid_amount' => $user->getTotalUnpaidMonthlyAmount(),
        ]);
    }
}
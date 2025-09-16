<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\SystemSetting;
use Tests\TestCase;

class TrialSubscriptionTransitionTest extends TestCase
{
    /** @test */
    public function it_calculates_remaining_trial_days_correctly()
    {
        // Set up a 14-day trial
        SystemSetting::set('trial_days', 14, 'integer', 'Default trial duration');
        
        // Create a user and start their trial
        $user = new User();
        $user->trial_ends_at = now()->addDays(12); // 12 days remaining
        $user->trial_used = true;
        
        // Test remaining days calculation
        $this->assertEquals(12, $user->getTrialDaysRemaining());
        
        // Test user is in trial period
        $this->assertTrue($user->isInTrialPeriod());
        
        // Test trial status
        $this->assertEquals('active', $user->getTrialStatus());
    }

    /** @test */
    public function it_handles_expired_trial_correctly()
    {
        // Create a user with expired trial
        $user = new User();
        $user->trial_ends_at = now()->subDays(5); // 5 days ago
        $user->trial_used = true;
        
        // Test remaining days calculation (should be 0)
        $this->assertEquals(0, $user->getTrialDaysRemaining());
        
        // Test user is not in trial period
        $this->assertFalse($user->isInTrialPeriod());
        
        // Test trial status
        $this->assertEquals('expired', $user->getTrialStatus());
    }

    /** @test */
    public function it_handles_no_trial_correctly()
    {
        // Create a user without trial
        $user = new User();
        $user->trial_ends_at = null;
        $user->trial_used = false;
        
        // Test remaining days calculation (should be 0)
        $this->assertEquals(0, $user->getTrialDaysRemaining());
        
        // Test user is not in trial period
        $this->assertFalse($user->isInTrialPeriod());
        
        // Test trial status
        $this->assertEquals('not_started', $user->getTrialStatus());
    }

    /** @test */
    public function it_starts_trial_with_14_day_duration()
    {
        // Set up a 14-day trial
        SystemSetting::set('trial_days', 14, 'integer', 'Default trial duration');
        
        // Create a user
        $user = new User();
        $user->trial_used = false;
        
        // Start trial
        $user->startTrial();
        
        // Test trial is set correctly
        $this->assertTrue($user->trial_used);
        $this->assertEquals(14, $user->getTrialDaysRemaining());
        $this->assertTrue($user->isInTrialPeriod());
        $this->assertEquals('active', $user->getTrialStatus());
        
        // Test trial end date is 14 days from now
        $expectedEndDate = now()->addDays(14);
        $this->assertEquals($expectedEndDate->format('Y-m-d'), $user->trial_ends_at->format('Y-m-d'));
    }

    /** @test */
    public function it_does_not_start_trial_if_already_used()
    {
        // Create a user who already used trial
        $user = new User();
        $user->trial_used = true;
        $user->trial_ends_at = now()->addDays(5);
        
        // Try to start trial again
        $user->startTrial();
        
        // Trial should remain unchanged
        $this->assertTrue($user->trial_used);
        $this->assertEquals(5, $user->getTrialDaysRemaining());
        $this->assertTrue($user->isInTrialPeriod());
    }
}

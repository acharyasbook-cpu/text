<?php

declare(strict_types=1);

final class CheckoutController
{
    public function __construct(
        private SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {
    }

    public function process(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }

        try {
            verify_csrf($_POST['_csrf'] ?? null);
        } catch (InvalidArgumentException $e) {
            flash('error', $e->getMessage());
            redirect('index.php');
        }

        $planId = (int) ($_POST['plan_id'] ?? 0);
        $return = safe_return_path($_POST['return'] ?? '');

        $plan = $this->subscriptions->findPlanById($planId);
        if (!$plan) {
            flash('error', 'Invalid subscription plan.');
            redirect($return !== '' ? ltrim($return, '/') : 'index.php');
        }

        $user = current_user();
        if (!$user) {
            $loginReturn = 'login.php?return=' . rawurlencode($return !== '' ? $return : $this->defaultReturnForPlan($plan));
            redirect($loginReturn);
        }

        if ($this->subscriptions->userHasActivePlanForSubCourse((int) $user['id'], (int) $plan['sub_course_id'])) {
            flash('success', 'You already have active access to this programme.');
            redirect(ltrim($this->defaultReturnForPlan($plan), '/'));
        }

        try {
            $result = $this->subscriptions->purchaseSubCoursePlan((int) $user['id'], $planId, 'acharya_checkout');
            flash(
                'success',
                'Payment successful! Reference ' . $result['transaction_ref']
                . ' — full access is now active.'
            );
        } catch (Throwable $e) {
            flash('error', 'Checkout failed: ' . $e->getMessage());
        }

        $dest = $return !== '' ? ltrim($return, '/') : ltrim($this->defaultReturnForPlan($plan), '/');
        redirect($dest);
    }

    /** @param array<string,mixed> $plan */
    private function defaultReturnForPlan(array $plan): string
    {
        $course = (string) ($plan['course_slug'] ?? '');
        $sub = (string) ($plan['sub_course_slug'] ?? '');
        if ($course !== '' && $sub !== '') {
            return '/sub_course.php?course=' . rawurlencode($course) . '&sub=' . rawurlencode($sub);
        }

        return '/dashboard.php';
    }
}

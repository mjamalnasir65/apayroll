<?php
function checkSubscription() {
    if ($_SESSION['role'] !== 'worker') {
        return true; // Non-workers don't need subscription
    }

    if ($_SESSION['sub_status'] !== 'active') {
        redirect('worker/subscription.php');
    }

    return true;
}

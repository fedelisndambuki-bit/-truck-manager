<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Attempt to auto-charge subscriptions due for renewal (before the 8am
// reminders run, so an expiry decision reflects today's charge attempt)
Schedule::command('subscription:process-renewals')->dailyAt('07:00');

// Send trial expiry and subscription renewal reminders every day at 8am
Schedule::command('subscription:reminders')->dailyAt('08:00');

// Escalate access requests stuck in review past the SLA window
Schedule::command('access-requests:escalate-sla')->dailyAt('09:00');

// Anonymize old rejected access requests / purge abandoned ones per the data retention policy
Schedule::command('access-requests:purge-retention')->dailyAt('03:00');

// Notify relevant users about active sites missing yesterday's daily site diary
Schedule::command('diaries:check-missing')->dailyAt('18:00');

// Notify relevant users about due/overdue/expiring safety items
Schedule::command('safety:send-due-notifications')->dailyAt('07:00');

// Notify relevant users about overdue invoices, retention due, cost variance, cash flow risk and forecast changes
Schedule::command('finance:send-due-notifications')->dailyAt('07:30');

// Notify relevant users about RFIs due soon and contracts expiring soon
Schedule::command('documents:send-due-notifications')->dailyAt('08:00');

// Notify relevant users about budget risk, project delays, rising safety risk and KPI threshold breaches
Schedule::command('bi:send-risk-notifications')->dailyAt('07:45');

// Generate and deliver every scheduled report whose next_run_at has passed
Schedule::command('bi:dispatch-scheduled-reports')->hourly();

// Escalate overdue approval steps past their SLA and complete elapsed Timer nodes
Schedule::command('workflow:process-escalations')->hourly();

// Notify relevant users about low stockpiles, equipment/plant breakdowns, maintenance due, low fuel and delayed dispatches
Schedule::command('quarry:send-alerts')->hourly();

// Notify relevant users about scheduled/approved/delayed/completed blasts, expiring permits, low explosive stock, exceeded environmental thresholds and pending post-blast analysis
Schedule::command('blast:send-alerts')->hourly();

// Notify relevant users about stopped plants, crusher/screen/conveyor breakdowns, due maintenance, below-target production, high energy usage, failed quality tests and pending approvals
Schedule::command('plant:send-alerts')->hourly();

// Notify relevant users about stock below minimum, full stockpiles, expiring reservations, large adjustment requests, pending quality approvals, inventory variances, completed transfers and physical counts due
Schedule::command('stockpile:send-alerts')->hourly();

// Notify relevant users about truck check-ins, long waits, loading progress, reloads, weight tolerance breaches, dispatch approvals, vehicle exits, low stock and offline scales
Schedule::command('weighbridge:send-alerts')->hourly();

// Notify relevant users about dispatch scheduling, truck assignment, loading, departure, delays, arrival, and delivery confirmation/failure
Schedule::command('dispatch:send-alerts')->hourly();

// Notify relevant users about sample receipt, testing due, test failures, quality holds/releases, calibration due, certificate generation, and pending quality approvals
Schedule::command('lab:send-alerts')->hourly();

// Notify relevant users about maintenance due, breakdowns reported, overdue inspections, expiring warranties, low spare parts, detected fuel theft, offline equipment, engine faults and due calibrations
Schedule::command('eam:send-alerts')->hourly();
Schedule::command('materials:send-alerts')->hourly();
Schedule::command('crm:send-alerts')->hourly();
Schedule::command('integration-engine:device:health-check')->hourly();

// Generate maintenance work orders for preventive maintenance plans that are due
Schedule::command('eam:generate-pm-work-orders')->hourly();

// Post this period's depreciation for every active equipment depreciation schedule
Schedule::command('eam:post-depreciation')->monthly();

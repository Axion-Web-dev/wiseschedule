# Setup Laravel Scheduler for Scheduled Posts

## Windows Task Scheduler Setup

1. Open **Task Scheduler** (search "Task Scheduler" in Start menu)
2. Click "Create Basic Task" on the right
3. Name: "Laravel Scheduler"
4. Description: "Run Laravel scheduler every minute for scheduled posts"
5. Trigger: Daily, repeat every 1 minute
6. Action: Start a program
7. Program: `C:\php\php.exe` (or your PHP path)
8. Arguments: `artisan schedule:run`
9. Start in: `c:\Users\New\social-engage`

## Alternative: Windows PowerShell Method

Create a batch file `run-scheduler.bat`:

```batch
@echo off
cd /d c:\Users\New\social-engage
php artisan schedule:run
```

Then schedule this batch file to run every minute.

## Linux/Mac Cron Setup

Add to crontab:
```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## Verify It's Working

After setting up the scheduler:
1. Wait 5 minutes
2. Check your scheduled posts should publish automatically
3. Check logs: `storage/logs/laravel.log`

## What This Does

- Every minute: Laravel checks for scheduled tasks
- Every 5 minutes: Triggers WordPress cron on all sites
- WordPress cron publishes posts whose scheduled time has arrived

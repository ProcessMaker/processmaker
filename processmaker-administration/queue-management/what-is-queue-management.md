---
description: Understand what Queue Management is in ProcessMaker 4.
---

# What is Queue Management?

## Overview

ProcessMaker 4 is built on the Laravel framework. [Laravel Horizon](https://horizon.laravel.com/) is a robust queue monitoring solution for Laravel-powered Redis queues. Use Laravel Horizon to monitor key metrics of your queue system such as job throughput, runtime, and job failures.

## What are Jobs and Queues?

### Jobs

In context with ProcessMaker 4, a job is any action in which ProcessMaker must perform. A job is something that ProcessMaker 4 must run for it to function properly.

Below are examples of ProcessMaker 4 jobs:

* A ProcessMaker 4 [user account is created](../add-users/manage-user-accounts/create-a-user-account.md).
* A ProcessMaker 4 [user profile is updated](../../using-processmaker/profile-settings.md#change-your-profile-settings).
* A [Request](../../using-processmaker/requests/what-is-a-request.md) is started.
* A [Task](../../using-processmaker/task-management/what-is-a-task.md) is created as part of a Request.

### Queues

A queue manages and monitors the sequence of jobs that ProcessMaker 4 must run. When a job is called, that job enters the queue. If there is no delay to run the job, the queue loads that job to run as soon as possible. However, some jobs are intentionally delayed from running immediately, such as a Timer Event element in a Request.

The queue have the following functions:

* The queue manages pending ProcessMaker 4 jobs regardless of whether a job is to run as soon as possible or at a later time.
* The queue monitors how efficiently jobs run in the ProcessMaker 4 server. Queue Management indicates via [dashboard metrics](dashboard.md) job throughput in the queue.

### Failed Jobs

A failed job is one in which ProcessMaker 4 has unsuccessfully attempted to run a job three \(3\) times. Thereafter, the job has failed. Queue Management displays failed jobs in both the [Dashboard](dashboard.md) and the [**Failed**](view-recently-failed-jobs.md) page.

## Related Topics

{% page-ref page="dashboard.md" %}

{% page-ref page="monitor-tags.md" %}

{% page-ref page="view-metrics.md" %}

{% page-ref page="view-recent-jobs.md" %}

{% page-ref page="view-recently-failed-jobs.md" %}


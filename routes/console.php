<?php

use App\Jobs\ElasticDumpJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new ElasticDumpJob, 'default')->everyTwoHours();

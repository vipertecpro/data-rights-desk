<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Vipertecpro\DataRightsDesk\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

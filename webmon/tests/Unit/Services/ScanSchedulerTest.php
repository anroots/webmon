<?php

namespace Tests\Unit;

use App\Jobs\ScanMock;
use App\Orm\Domain;
use App\Services\ScanSchedulerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScanSchedulerTest extends TestCase
{
    use DatabaseMigrations;
    /**
     * @var ScanSchedulerService
     */
    private $scanSchedulerService;

    protected function setUp()
    {
        parent::setUp();
        $this->scanSchedulerService = new ScanSchedulerService;
    }

    public function testScheduleScanDoesNotScheduleRecentlyUpdatedDomain()
    {
        $originalTime = Carbon::now()->subMinute(1);
        $domain = factory(Domain::class)->create(['updated_at' => $originalTime]);
        $scheduled = $this->scanSchedulerService->run();
        $this->assertEquals(0,$scheduled);
        $this->assertEquals(0,$domain->updated_at->diffInSeconds($originalTime));

    }

    public function testGetCandidateDomainsExcludesRecentlyUpdated()
    {

        factory(Domain::class)->create(['updated_at' => Carbon::now()->subMinute()]);
        $domain = factory(Domain::class)->create(['updated_at' => Carbon::now()->subMonth()]);
        $candidates = $this->scanSchedulerService->getCandidateDomains();

        $this->assertCount(1, $candidates);
        $this->assertEquals($domain->id, $candidates->first()->id);
    }
}

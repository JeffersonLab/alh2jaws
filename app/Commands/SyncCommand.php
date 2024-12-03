<?php

namespace App\Commands;

use App\Exceptions\SanityException;
use App\Model\AlhActionsList;
use App\Model\AlhAlarmsList;
use App\Model\JawsActionsList;
use App\Model\JawsAlarmsList;
use App\Service\Comparison;
use App\Service\JawsUpdater;
use Dotenv\Parser\Parser;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync {--f|force : bypass max updates limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync ALH alarms to JAWS';


    /**
     * Specify the maximum number of alarm changes that can be applied without
     * requiring the -f (-force) command switch.
     * @var int
     */
    protected $maxUpdates = 100;

    /**
     * Set true to bypass the maxUpdates limit.
     *
     * @var bool
     */
    protected $force = false;


    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->force = $this->option('force');
        // The plan is not to sync actions after the initial sync is complete.
        // Should that plan change, uncomment the handleActions() line below.
        // Actions typically would need to sync before the alarms that reference them.
        //$this->handleActions();

        // Sync alarm definitions
        try{
            $this->handleAlarms();
        } catch (\Exception $e){
            $this->error($e->getMessage());
            return 1; // shell exit code of failure
        }
        return 0;  // shell exit code of success

    }

    protected function handleAlarms()
    {
        $alh = new AlhAlarmsList();
        $jaws = new JawsAlarmsList();
        $updater = new JawsUpdater();
        $c = new Comparison($alh->alarms(), $jaws->alarms());

        $this->assertSanity($c);    // Protect against massive data clobber

        foreach ($c->notInAlh() as $item) {
            $jawsAlarm = (object)$item;
            try {
                $this->line("Must delete {$jawsAlarm->name} ($jawsAlarm->id)");
                $updater->removeAlarm($jawsAlarm);
                $this->line(".. Done");
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        foreach ($c->notInJaws() as $item) {
            $alhAlarm = (object)$item;
            try {
                $this->line("Must add {$alhAlarm->name} ($alhAlarm->pv)");
                $updater->addAlarm($alhAlarm);
                $this->line(".. Done");
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        foreach ($jaws->alarms() as $pv => $jawsAlarm){
            try{
                $alhAlarm = $alh->alarms()->get($pv);
                if ($alhAlarm->pv == $jawsAlarm->pv){
                    if ($c->differs($jawsAlarm, $alhAlarm)){
                        $this->line("Must update attributes of {$jawsAlarm->name}");
                        $jawsAlarm->name = $alhAlarm->name;
                        $jawsAlarm->action = $alhAlarm->action;
                        $jawsAlarm->location = $alhAlarm->location;
                        $jawsAlarm->screenCommand = $alhAlarm->screencommand;
                        $jawsAlarm->managedBy = $alhAlarm->managedby;  //annoying that Michele didn't camelcase.
                        $updater->editAlarm($jawsAlarm);
                        $this->line(".. Done");
                    }
                }
            } catch (\Exception $e){
                $this->error($e->getMessage());
            }

        }
    }


    protected function handleActions()
    {
        $alhActions = new AlhActionsList();
        $jawsActions = new JawsActionsList();
        $updater = new JawsUpdater();


        $this->line('---- ALH Alarm Actions Without Jaws Match ----');
        foreach ($alhActions->actions() as $alhAction) {
            if ($jawsActions->distinctActionNames()->doesntContain($alhAction->name)) {
                try {
                    $this->line("Must add {$alhAction->name}");
                    $updater->addAction($alhAction);
                    $this->line(".. Done");
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }

            }
        }

        $this->line('---- JAWS Actions Without Match ----');
        foreach ($jawsActions->actions() as $jawsAction) {
            $jawsAction = (object)$jawsAction;
            if ($alhActions->distinctActionNames()->doesntContain($jawsAction->name)) {
                try {
                    $this->line("Must delete {$jawsAction->name}");
                    $updater->removeAction($jawsAction);
                    $this->line(".. Done");
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
        }
    }


    protected function assertSanity(Comparison $c){
        if (! $this->force){
            $mustThrow = false;
            if ($c->notInJaws()->count() > $this->maxUpdates){
                $mustThrow = true;
            }
            if ($c->notInAlh()->count() > $this->maxUpdates){
                $mustThrow = true;
            }
            if ($c->differences()->count() > $this->maxUpdates){
                $mustThrow = true;
            }
            if ($mustThrow){
                $message = "The number of pending updates exceeds the sanity check threshold. \n";
                $message .= "If you feel it's safe to proceed, use the -f flag to bypass this check.";

                throw new SanityException($message);
            }
        }
    }


    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}

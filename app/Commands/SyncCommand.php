<?php

namespace App\Commands;

use App\Model\AlhActionsList;
use App\Model\AlhAlarmsList;
use App\Model\JawsActionsList;
use App\Model\JawsAlarmsList;
use App\Service\Comparison;
use App\Service\JawsUpdater;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync ALH alarms to JAWS';

    /**
     * Execute the console command.
     */
    public function handle()
    {

//     $u = new JawsUpdater();
//     $a = new AlhAlarmsList();
//     $item = $a->alarms()->first();
//     $item->name = 'foo12'; $item->pv='zzz12';
//     $u->addAlarm($item);
//     return true;

//        $this->handleActions();
        return true;

        $alh = new AlhAlarmsList();
        $jaws = new JawsAlarmsList();

        //dd($alh->distinctActionNames()->all());

        $c = new Comparison($alh->alarms(), $jaws->alarms());



    }

    protected function handleActions(){
        $alhActions = new AlhActionsList();
        $jawsActions = new JawsActionsList();
        $updater = new JawsUpdater();

        $this->line('---- ALH Alarm Actions Without Jaws Match ----');
        foreach ($alhActions->actions() as $alhAction){
            if ($jawsActions->distinctActionNames()->doesntContain($alhAction->name)){
                try{
                    $this->line("Must add {$alhAction->name}");
                    $updater->addAction($alhAction);
                    $this->line(".. Done");
                }catch(\Exception $e){
                    $this->error($e->getMessage());
                }

            }
        }

        $this->line('---- JAWS Actions Without Match ----');
        foreach ($jawsActions->actions() as $jawsAction){
            $jawsAction = (object) $jawsAction;
            if ($alhActions->distinctActionNames()->doesntContain($jawsAction->name)){
                try{
                    $this->line("Must delete {$jawsAction->name}");
                    $updater->removeAction($jawsAction);
                    $this->line(".. Done");
                }catch(\Exception $e){
                    $this->error($e->getMessage());
                }
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

<?php

namespace App\Commands;

use App\Model\AlhAlarmsList;
use App\Model\JawsAlarmsList;
use App\Service\Comparison;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\{render};

class CompareCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'compare ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Compare ALH to Jaws and Print the differences';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $l = new AlhAlarmsList();
        $alh = $l->alarms();
        $l = new JawsAlarmsList();
        $jaws = $l->alarms();
        $c = new Comparison($alh, $jaws);

        $this->line("Not in JAWS: ". $c->notInJaws()->count());
        if ($this->output->isVerbose()){
            foreach ($c->notInJaws()->keys() as $key){
                $this->line("Missing from Jaws: ".$key);
            }
        }
        $this->line("Not in ALH: ". $c->notInAlh()->count());
        if ($this->output->isVerbose()){
            foreach ($c->notInAlh()->keys() as $key){
                $this->line("Missing from Alh: ".$key);
            }
        }
//        foreach ($c->differences() as $key => $items){
//            foreach ($items as $item){
//                $this->line($key . ' ' . $item);
//            }
//        }

    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}

<?php

namespace App\Commands;

use App\Model\AlhActionsList;
use App\Model\AlhAlarmsList;
use App\Model\JawsActionsList;
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
    protected $signature = 'compare';

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
        $alh = new AlhAlarmsList();
        $alhActions = new AlhActionsList();
        $jaws = new JawsAlarmsList();
        $jawsActions = new JawsActionsList();

        $c = new Comparison($alh->alarms(), $jaws->alarms());

        $this->line('ALH Actions With no Jaws Action Match: ' . $this->countAlhWithoutJawsMatch($jawsActions, $alh));
        if ($this->output->isVerbose()) {
            foreach ($alh->distinctActionNames() as $alhAction) {
                if ($jawsActions->distinctActionNames()->doesntContain($alhAction)) {
                    $this->line("$alhAction");
                }
            }
        }

        $this->line('ALH Actions With no ALH Alarm Match: ' . $this->countAlhWithoutAlhMatch($alhActions, $alh));
        if ($this->output->isVerbose()) {
            foreach ($alh->distinctActionNames() as $alhAction) {
                if ($alhActions->distinctActionNames()->doesntContain($alhAction)) {
                    $this->line("$alhAction");
                }
            }
        }

        $this->line('JAWS Actions With no ALH Alarm Match: ' . $this->countJawsWithoutMatch($jawsActions, $alh));
        if ($this->output->isVerbose()) {
            foreach ($jawsActions->distinctActionNames() as $jawsAction) {
                if ($alh->distinctActionNames()->doesntContain($jawsAction)) {
                    $this->line("$jawsAction");
                }
            }
        }


        $this->line("ALH Alarms Not in JAWS: ". $c->notInJaws()->count());
        if ($this->output->isVerbose()){
            foreach ($c->notInJaws()->keys() as $key){
                $this->line("Missing from Jaws: ".$key);
            }
        }
        $this->line("JAWS Alarms Not in ALH: ". $c->notInAlh()->count());
        if ($this->output->isVerbose()){
            foreach ($c->notInAlh()->keys() as $key){
                $this->line("Missing from Alh: ".$key);
            }
        }
        $this->line("Alarm Attribute Mismatches : ". $c->differences()->count());
        if ($this->output->isVerbose()){
            foreach ($c->differences() as $key => $items) {
                foreach ($items as $item) {
                    $this->line($key . ' ' . $item);
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



    public function countJawsWithoutMatch($jawsActions, $alh){
        $count = 0;
        foreach ($jawsActions->distinctActionNames() as $jawsAction) {
            if ($alh->distinctActionNames()->doesntContain($jawsAction)) {
                $count++;
            }
        }
        return $count;
    }

    public function countAlhWithoutAlhMatch($alhActions, $alh){
        $count = 0;
        foreach ($alh->distinctActionNames() as $alhAction){
            if ($alhActions->distinctActionNames()->doesntContain($alhAction)){
                $count++;
            }
        }
        return $count;
    }

    protected function countAlhWithoutJawsMatch($jawsActions, $alh){
        $count = 0;
        foreach ($alh->distinctActionNames() as $alhAction) {
            if ($jawsActions->distinctActionNames()->doesntContain($alhAction)) {
                $count++;
            }
        }
        return $count;
    }

}

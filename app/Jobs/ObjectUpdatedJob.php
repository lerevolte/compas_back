<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ObjectUpdatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $entity_class;
    private $objects;
    private $changed_fields;

    public function __construct($entity_class, $objects, $changed_fields)
    {
        $this->entity_class = $entity_class;
        $this->objects = $objects;
        $this->changed_fields = $changed_fields;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        info('handle1');
    	sleep(3);

        $settings = \App\Models\Settings::get(true);
        info('handle2');
        $objects_collection = $this->entity_class::whereIntegerInRaw('id', $this->objects)->get();
        $objects = $objects_collection->keyBy('id');
        foreach($objects as $ob) {
            $data = $ob->getData($this->changed_fields);
            \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);
        };
    	
        //\App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);

    }
}

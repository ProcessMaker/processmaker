<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\User;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a process
 */
$factory->define(Process::class, function (Faker $faker) {

    return [
        'uid' => Uuid::uuid4(),
        'name' => $faker->sentence(3),
        'description' => $faker->paragraph(3),
        'status' => 'ACTIVE',
        'type' => 'NORMAL',
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'process_category_id' => function () {
            return factory(ProcessCategory::class)->create()->id;
        },
        'bpmn' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><definitions xmlns="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" xmlns:tns="http://sourceforge.net/bpmn/definitions/_1527096041313" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:yaoqiang="http://bpmn.sourceforge.net" exporter="Yaoqiang BPMN Editor" exporterVersion="5.3" expressionLanguage="http://www.w3.org/1999/XPath" id="_1527096041313" name="" targetNamespace="http://sourceforge.net/bpmn/definitions/_1527096041313" typeLanguage="http://www.w3.org/2001/XMLSchema" xsi:schemaLocation="http://www.omg.org/spec/BPMN/20100524/MODEL http://bpmn.sourceforge.net/schemas/BPMN20.xsd"></definitions>>'
    ];
});

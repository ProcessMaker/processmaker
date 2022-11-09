<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Models\User;
use ProcessMaker\Package\PackageProcessOptimization\Simulator\Simulator;

class SimulationTest extends TestCase
{
    /**
     * @var object
     */
    protected $payload;

    /**
     * @var User
     */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->payload = '<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd" xmlns:tns="http://sourceforge.net/bpmn/definitions/_1530553328908" xmlns:xsd="http://www.w3.org/2001/XMLSchema" targetNamespace="http://bpmn.io/schema/bpmn" exporter="ProcessMaker Modeler" exporterVersion="1.0" xsi:schemaLocation="http://www.omg.org/spec/BPMN/20100524/MODEL http://bpmn.sourceforge.net/schemas/BPMN20.xsd">
	<bpmn:process id="ProcessId" name="ProcessName" isExecutable="true">
		<bpmn:endEvent id="node_4" name="End Event">
			<bpmn:incoming>node_20</bpmn:incoming>
			<bpmn:incoming>node_6</bpmn:incoming>
		</bpmn:endEvent>
		<bpmn:task id="node_7" name="Form Task" pm:screenRef="289" pm:allowInterstitial="false" pm:assignment="requester" pm:assignmentLock="false" pm:allowReassignment="false" pm:config="{&#34;reactions&#34;:false,&#34;voting&#34;:false,&#34;edit_comments&#34;:false,&#34;remove_comments&#34;:false,&#34;web_entry&#34;:null,&#34;email_notifications&#34;:{&#34;notifications&#34;:[]}}">
			<bpmn:incoming>node_16</bpmn:incoming>
			<bpmn:outgoing>node_20</bpmn:outgoing>
		</bpmn:task>
		<bpmn:sequenceFlow id="node_20" name="" sourceRef="node_7" targetRef="node_4"/>
		<bpmn:serviceTask id="node_27" name="Send Email Test" pm:config="{&#34;emailServer&#34;:&#34;&#34;,&#34;type&#34;:&#34;text&#34;,&#34;subject&#34;:&#34;Email Test for Escalation&#34;,&#34;textBody&#34;:&#34;Did it work?&#34;,&#34;screenRef&#34;:null,&#34;users&#34;:[],&#34;groups&#34;:[],&#34;addEmails&#34;:[&#34;eric.wallace@ellucian.com&#34;],&#34;requestRecipients&#34;:[],&#34;usersAndGroupsOptionSelected&#34;:false}" implementation="connector-send-email/processmaker-communication-email-send">
			<bpmn:documentation>&lt;p&gt;Send Email test&lt;/p&gt;</bpmn:documentation>
			<bpmn:incoming>node_11</bpmn:incoming>
			<bpmn:outgoing>node_6</bpmn:outgoing>
		</bpmn:serviceTask>
		<bpmn:sequenceFlow id="node_6" name="" sourceRef="node_27" targetRef="node_4"/>
		<bpmn:boundaryEvent id="node_9" name="Boundary Timer Event" attachedToRef="node_7">
			<bpmn:outgoing>node_11</bpmn:outgoing>
			<bpmn:timerEventDefinition>
				<bpmn:timeDuration>PT1M</bpmn:timeDuration>
			</bpmn:timerEventDefinition>
		</bpmn:boundaryEvent>
		<bpmn:sequenceFlow id="node_11" name="" sourceRef="node_9" targetRef="node_27"/>
		<bpmn:startEvent id="node_14" name="Start Event" pm:allowInterstitial="false" pm:assignment="user_group" pm:assignedUsers="1" pm:config="{&#34;web_entry&#34;:null}">
			<bpmn:outgoing>node_16</bpmn:outgoing>
		</bpmn:startEvent>
		<bpmn:sequenceFlow id="node_16" name="" sourceRef="node_14" targetRef="node_7"/>
	</bpmn:process>
</bpmn:definitions>';
        $this->user = User::first();
    }

    public function testUnreachableElements()
    {
        $handler = new Simulator($this->payload, $this->user);
        $reports = array_filter($handler->run(), function($item){
            return $item['status'] === 'UNREACHABLE';
        });
        $this->assertCount(0,$reports);
    }
}

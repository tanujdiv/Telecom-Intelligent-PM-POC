<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;

class PocSampleSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::firstOrCreate(
            ['code' => 'TEL-POC-01'],
            [
                'name'        => 'Telecom Core & Field Ops POC',
                'description' => 'Proof of concept for AI-assisted SOP/MOP management, task tracking, and MCP workflows.',
                'status'      => 'active',
                'start_date'  => now()->toDateString(),
                'end_date'    => now()->addDays(30)->toDateString(),
            ]
        );

        Task::firstOrCreate(
            ['project_id' => $project->id, 'title' => 'Daily Core Router Health Check'],
            [
                'description' => 'Verify CPU, memory, BGP peer status, and interface packet drops.',
                'category'    => 'network',
                'priority'    => 'high',
                'status'      => 'in_progress',
                'due_at'      => now()->addDay(),
            ]
        );

        Task::firstOrCreate(
            ['project_id' => $project->id, 'title' => 'Prepare Fiber Cutover MOP v1.0'],
            [
                'description' => 'Draft maintenance window procedure with pre-checks and rollback steps.',
                'category'    => 'sop_mop',
                'priority'    => 'medium',
                'status'      => 'pending',
                'due_at'      => now()->addDays(3),
            ]
        );

        $sopText = "Network Maintenance SOP (SOP-NET-001 v1.0):\n"
            . "1. Take pre-change baseline of interface traffic, CPU (<75%), and memory (<80%).\n"
            . "2. Confirm maintenance window approval and notify watchers.\n"
            . "3. Execute maintenance step-by-step and verify link/service health.\n"
            . "4. If error rate spikes or link flaps occur, run rollback steps immediately and escalate to L2.";

        $doc = Document::firstOrCreate(
            ['doc_code' => 'SOP-NET-001'],
            [
                'project_id'   => $project->id,
                'doc_type'     => 'SOP',
                'version'      => 'v1.0',
                'title'        => 'Network Maintenance & Health Check SOP',
                'status'       => 'approved',
                'content_text' => $sopText,
            ]
        );

        DocumentChunk::firstOrCreate(
            ['document_id' => $doc->id, 'chunk_index' => 0],
            [
                'section_title' => 'Standard Maintenance Procedure',
                'chunk_text'    => $sopText,
            ]
        );

        Issue::firstOrCreate(
            ['ticket_no' => 'INC-1001'],
            [
                'project_id'  => $project->id,
                'title'       => 'High latency on Regional Aggregation Link',
                'description' => 'Packet latency rose above threshold during peak hours; requires SOP verification.',
                'severity'    => 'high',
                'status'      => 'open',
            ]
        );
    }
}
@php
    $nodeTypes = \App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource\Pages\DesignWorkflow::nodeTypeCatalog();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    <link rel="stylesheet" href="https://unpkg.com/drawflow@0.0.60/dist/drawflow.min.css" />

    <x-filament::section :compact="true" collapsible collapsed>
        <x-slot name="heading">
            <span class="flex items-center gap-2">
                {{ $record->name }}
                <x-filament::badge :color="match($record->status) { 'Published' => 'success', 'Archived' => 'gray', default => 'warning' }">
                    {{ $record->status }} · v{{ $record->version }}
                </x-filament::badge>
            </span>
        </x-slot>
        <x-slot name="description">Workflow settings — click to expand</x-slot>

        <form wire:submit.prevent="$refresh">
            {{ $this->form }}
        </form>
    </x-filament::section>

    <div
        wire:ignore
        x-data="{
            editor: null,
            initialGraph: @js($graph),
            nodeTypes: @js($nodeTypes),
            approverRoles: @js($this->getApproverRoleOptions()),
            departments: @js($this->getDepartmentOptions()),
            selectedNodeId: null,
            selectedNodeType: null,
            selectedNodeConfig: {},
            selectedEdge: null,
            selectedEdgeCondition: { field: '', operator: '=', value: '' },

            init() {
                const loadDrawflow = () => new Promise((resolve) => {
                    if (window.Drawflow) { resolve(); return; }
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/drawflow@0.0.60/dist/drawflow.min.js';
                    script.onload = () => resolve();
                    document.head.appendChild(script);
                });

                loadDrawflow().then(() => {
                    this.editor = new Drawflow(this.$refs.canvas);
                    this.editor.reroute = true;
                    this.editor.start();
                    this.importGraph(this.initialGraph);

                    this.editor.on('nodeSelected', (id) => this.selectNode(id));
                    this.editor.on('nodeUnselected', () => { this.selectedNodeId = null; this.selectedNodeType = null; });
                    this.editor.on('connectionSelected', (info) => this.selectEdge(info));
                    this.editor.on('connectionUnselected', () => { this.selectedEdge = null; });

                    this.$refs.canvas.addEventListener('dragover', (e) => e.preventDefault());
                    this.$refs.canvas.addEventListener('drop', (e) => this.onCanvasDrop(e));
                });
            },

            onPaletteDragStart(event, type) {
                event.dataTransfer.setData('node-type', type);
            },

            onCanvasDrop(event) {
                event.preventDefault();
                const type = event.dataTransfer.getData('node-type');
                if (!type) return;
                const rect = this.$refs.canvas.getBoundingClientRect();
                const x = (event.clientX - rect.left - 60) / (this.editor.zoom || 1);
                const y = (event.clientY - rect.top - 20) / (this.editor.zoom || 1);
                this.addNode(type, x, y);
            },

            addNode(type, x, y) {
                const meta = this.nodeTypes[type] || { inputs: 1, outputs: 1, label: type, color: '#6b7280' };
                const html = `<div class='p-2 text-xs font-semibold text-white rounded' style='background-color:${meta.color}'>${meta.label}</div>`;
                return this.editor.addNode(type, meta.inputs, meta.outputs, x, y, type, { type, config: {} }, html, false);
            },

            selectNode(id) {
                const node = this.editor.getNodeFromId(id);
                if (!node) return;
                this.selectedNodeId = String(id);
                this.selectedNodeType = node.data.type || node.name;
                this.selectedNodeConfig = node.data.config || {};
                this.selectedEdge = null;
            },

            applyConfig() {
                if (!this.selectedNodeId) return;
                const node = this.editor.getNodeFromId(this.selectedNodeId);
                this.editor.updateNodeDataFromId(this.selectedNodeId, { type: this.selectedNodeType, config: this.selectedNodeConfig, edgeMeta: node.data.edgeMeta || {} });
            },

            selectEdge(info) {
                this.selectedEdge = info;
                this.selectedNodeId = null;
                const node = this.editor.getNodeFromId(info.output_id);
                const meta = (node.data.edgeMeta || {})[info.output_class] || {};
                this.selectedEdgeCondition = meta.condition ? { ...meta.condition } : { field: '', operator: '=', value: '' };
            },

            applyEdgeCondition() {
                if (!this.selectedEdge) return;
                const node = this.editor.getNodeFromId(this.selectedEdge.output_id);
                const edgeMeta = node.data.edgeMeta || {};
                edgeMeta[this.selectedEdge.output_class] = {
                    condition: this.selectedEdgeCondition.field ? { ...this.selectedEdgeCondition } : null,
                };
                this.editor.updateNodeDataFromId(this.selectedEdge.output_id, { ...node.data, edgeMeta });
            },

            deleteSelected() {
                if (this.selectedNodeId) {
                    this.editor.removeNodeId('node-' + this.selectedNodeId);
                    this.selectedNodeId = null;
                }
            },

            importGraph(graph) {
                const data = {};
                (graph.nodes || []).forEach((node) => {
                    const meta = this.nodeTypes[node.type] || { inputs: 1, outputs: 1, label: node.type, color: '#6b7280' };
                    const outputs = {};
                    for (let i = 1; i <= Math.max(meta.outputs, 1); i++) outputs['output_' + i] = { connections: [] };
                    const inputs = {};
                    for (let i = 1; i <= Math.max(meta.inputs, 0); i++) inputs['input_' + i] = { connections: [] };
                    data[node.id] = {
                        id: parseInt(node.id), name: node.type,
                        data: { type: node.type, config: node.config || {}, edgeMeta: {} },
                        class: node.type,
                        html: `<div class='p-2 text-xs font-semibold text-white rounded' style='background-color:${meta.color}'>${meta.label}</div>`,
                        typenode: false, inputs, outputs,
                        pos_x: node.position?.x ?? 100, pos_y: node.position?.y ?? 100,
                    };
                });

                const outIndex = {};
                (graph.edges || []).forEach((edge) => {
                    outIndex[edge.from] = (outIndex[edge.from] || 0) + 1;
                    const outSlot = 'output_' + outIndex[edge.from];
                    const inSlot = 'input_1';
                    if (data[edge.from] && data[edge.from].outputs[outSlot]) {
                        data[edge.from].outputs[outSlot].connections.push({ node: String(edge.to), output: inSlot });
                        data[edge.from].data.edgeMeta[outSlot] = { condition: edge.condition || null };
                    }
                    if (data[edge.to] && data[edge.to].inputs[inSlot]) {
                        data[edge.to].inputs[inSlot].connections.push({ node: String(edge.from), input: outSlot });
                    }
                });

                if (Object.keys(data).length > 0) {
                    this.editor.import({ drawflow: { Home: { data } } });
                }
            },

            exportGraph() {
                const exported = this.editor.export();
                const data = exported.drawflow.Home.data;
                const nodes = [];
                const edges = [];

                Object.keys(data).forEach((id) => {
                    const n = data[id];
                    nodes.push({
                        id: String(id),
                        type: n.data.type || n.name,
                        position: { x: n.pos_x, y: n.pos_y },
                        config: n.data.config || {},
                    });

                    Object.keys(n.outputs || {}).forEach((outKey) => {
                        const meta = (n.data.edgeMeta || {})[outKey] || {};
                        (n.outputs[outKey].connections || []).forEach((conn) => {
                            edges.push({ from: String(id), to: String(conn.node), condition: meta.condition || null });
                        });
                    });
                });

                return { nodes, edges };
            },

            saveDraft() {
                this.$wire.call('saveDraft', this.exportGraph());
            },

            publish() {
                this.$wire.call('publish', this.exportGraph());
            },
        }"
        class="mt-4 grid grid-cols-12 gap-4"
    >
        {{-- Palette --}}
        <div class="col-span-2 space-y-2">
            <x-filament::section :compact="true">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Nodes</div>
                <div class="space-y-1.5">
                    <template x-for="[key, meta] in Object.entries(nodeTypes)" :key="key">
                        <div
                            draggable="true"
                            @dragstart="onPaletteDragStart($event, key)"
                            class="cursor-grab select-none rounded-md border px-2 py-1.5 text-xs font-medium text-white text-center"
                            :style="'background-color:' + meta.color + '; border-color:' + meta.color"
                            x-text="meta.label"
                        ></div>
                    </template>
                </div>
                <p class="mt-3 text-[11px] text-gray-400">Drag a node onto the canvas, then connect nodes by dragging from the small connector dots.</p>
            </x-filament::section>
        </div>

        {{-- Canvas --}}
        <div class="col-span-7">
            <x-filament::section :compact="true">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex gap-1">
                        <button type="button" @click="editor.zoom_in()" class="fi-btn rounded-md h-7 w-7 inline-flex items-center justify-center text-gray-500 hover:text-gray-800 dark:hover:text-white border">+</button>
                        <button type="button" @click="editor.zoom_out()" class="fi-btn rounded-md h-7 w-7 inline-flex items-center justify-center text-gray-500 hover:text-gray-800 dark:hover:text-white border">−</button>
                        <button type="button" @click="editor.zoom_reset()" class="fi-btn rounded-md h-7 px-2 inline-flex items-center justify-center text-xs text-gray-500 hover:text-gray-800 dark:hover:text-white border">Reset</button>
                        <button type="button" @click="deleteSelected()" class="fi-btn rounded-md h-7 px-2 inline-flex items-center justify-center text-xs text-danger-600 border border-danger-300">Delete Selected</button>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="saveDraft()"
                                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none rounded-lg h-8 pl-3 pr-3 text-xs inline-grid gap-1 shadow-sm bg-gray-600 text-white hover:bg-gray-500">
                            Save Draft
                        </button>
                        <button type="button" @click="publish()"
                                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none rounded-lg h-8 pl-3 pr-3 text-xs inline-grid gap-1 shadow-sm bg-primary-600 text-white hover:bg-primary-500">
                            Publish
                        </button>
                    </div>
                </div>
                <div id="drawflow-canvas" x-ref="canvas" class="rounded-xl border border-gray-200 dark:border-white/10" style="height: 560px; width: 100%; position: relative; background-size: 20px 20px; background-image: radial-gradient(circle, #d1d5db 1px, rgba(0,0,0,0) 1px);"></div>
            </x-filament::section>
        </div>

        {{-- Node config side panel --}}
        <div class="col-span-3">
            <x-filament::section :compact="true">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Node Settings</div>

                <template x-if="!selectedNodeId">
                    <p class="text-xs text-gray-400">Select a node on the canvas to configure it.</p>
                </template>

                <template x-if="selectedNodeId">
                    <div class="space-y-3 text-xs">
                        <div class="font-semibold text-gray-800 dark:text-gray-200" x-text="nodeTypes[selectedNodeType]?.label"></div>

                        <template x-if="['approval_step','document_verification','signature'].includes(selectedNodeType)">
                            <div class="space-y-2">
                                <label class="block text-gray-500">Approver Type</label>
                                <select x-model="selectedNodeConfig.approver_type" @change="applyConfig()" class="fi-select-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs">
                                    <option value="role">Role</option>
                                    <option value="department">Department (all staff)</option>
                                    <option value="department_head">Department Head</option>
                                    <option value="supervisor_chain">Supervisor Chain</option>
                                    <option value="specific_user">Specific User (ID)</option>
                                    <option value="committee">Committee Role</option>
                                </select>

                                <template x-if="selectedNodeConfig.approver_type === 'role'">
                                    <div>
                                        <label class="block text-gray-500 mt-2">Role</label>
                                        <select x-model="selectedNodeConfig.role" @change="applyConfig()" class="fi-select-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs">
                                            <template x-for="role in approverRoles" :key="role"><option :value="role" x-text="role"></option></template>
                                        </select>
                                    </div>
                                </template>

                                <template x-if="['department','department_head'].includes(selectedNodeConfig.approver_type)">
                                    <div>
                                        <label class="block text-gray-500 mt-2">Department</label>
                                        <select x-model.number="selectedNodeConfig.department_id" @change="applyConfig()" class="fi-select-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs">
                                            <template x-for="[id, name] in Object.entries(departments)" :key="id"><option :value="Number(id)" x-text="name"></option></template>
                                        </select>
                                    </div>
                                </template>

                                <template x-if="selectedNodeConfig.approver_type === 'supervisor_chain'">
                                    <div>
                                        <label class="block text-gray-500 mt-2">Level (1 = direct supervisor)</label>
                                        <input type="number" min="1" x-model.number="selectedNodeConfig.level" @input="applyConfig()" class="fi-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs" />
                                    </div>
                                </template>

                                <template x-if="selectedNodeConfig.approver_type === 'specific_user'">
                                    <div>
                                        <label class="block text-gray-500 mt-2">User ID</label>
                                        <input type="number" x-model.number="selectedNodeConfig.user_id" @input="applyConfig()" class="fi-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs" />
                                    </div>
                                </template>

                                <template x-if="selectedNodeConfig.approver_type === 'committee'">
                                    <div>
                                        <label class="block text-gray-500 mt-2">Committee Role</label>
                                        <select x-model="selectedNodeConfig.committee_role" @change="applyConfig()" class="fi-select-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs">
                                            <template x-for="role in approverRoles" :key="role"><option :value="role" x-text="role"></option></template>
                                        </select>
                                    </div>
                                </template>

                                <label class="block text-gray-500 mt-2">Resolution Mode</label>
                                <select x-model="selectedNodeConfig.approver_resolution_mode" @change="applyConfig()" class="fi-select-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs">
                                    <option value="any_one">Any one approver</option>
                                    <option value="all_required">All required (consensus)</option>
                                    <option value="majority">Majority vote</option>
                                    <option value="single">Single (first assigned)</option>
                                </select>

                                <label class="block text-gray-500 mt-2">Escalate after (hours)</label>
                                <input type="number" min="1" x-model.number="selectedNodeConfig.escalation_hours" @input="applyConfig()" class="fi-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs" />
                            </div>
                        </template>

                        <template x-if="['decision','condition'].includes(selectedNodeType)">
                            <p class="text-gray-500">Set a condition on each outgoing connection from this node (click a connection below).</p>
                        </template>

                        <template x-if="selectedNodeType === 'timer'">
                            <div>
                                <label class="block text-gray-500">Delay (hours)</label>
                                <input type="number" min="1" x-model.number="selectedNodeConfig.hours" @input="applyConfig()" class="fi-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs" />
                            </div>
                        </template>

                        <template x-if="selectedNodeType === 'notification'">
                            <div>
                                <label class="block text-gray-500">Message</label>
                                <textarea x-model="selectedNodeConfig.message" @input="applyConfig()" rows="2" class="fi-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs"></textarea>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="selectedEdge">
                    <div class="mt-4 pt-3 border-t space-y-2 text-xs">
                        <div class="font-semibold text-gray-800 dark:text-gray-200">Connection Condition</div>
                        <label class="block text-gray-500">Field</label>
                        <input type="text" x-model="selectedEdgeCondition.field" @input="applyEdgeCondition()" placeholder="e.g. amount" class="fi-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs" />
                        <label class="block text-gray-500">Operator</label>
                        <select x-model="selectedEdgeCondition.operator" @change="applyEdgeCondition()" class="fi-select-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs">
                            <option value="=">=</option><option value="!=">!=</option>
                            <option value=">">&gt;</option><option value="<">&lt;</option>
                            <option value=">=">&gt;=</option><option value="<=">&lt;=</option>
                            <option value="contains">contains</option>
                        </select>
                        <label class="block text-gray-500">Value</label>
                        <input type="text" x-model="selectedEdgeCondition.value" @input="applyEdgeCondition()" class="fi-input block w-full rounded-md border-gray-300 dark:bg-gray-700 text-xs" />
                        <p class="text-[11px] text-gray-400">Leave field blank for the default/else branch.</p>
                    </div>
                </template>
            </x-filament::section>

            @if (!empty($this->getVersionHistory()))
                <x-filament::section :compact="true" class="mt-3">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Version History</div>
                    <div class="space-y-1.5">
                        @foreach ($this->getVersionHistory() as $v)
                            <div class="flex items-center justify-between text-xs">
                                <span>v{{ $v['version'] }} — {{ $v['published_at'] }}</span>
                                <button type="button" wire:click="rollback({{ $v['id'] }})" wire:confirm="Roll back to version {{ $v['version'] }}?" class="text-primary-600 hover:underline">Rollback</button>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>

</x-filament-panels::page>

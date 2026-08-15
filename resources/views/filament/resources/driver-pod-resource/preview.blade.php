<div class="flex flex-col gap-4" x-data="{ 
    scale: 1, 
    rotate: 0,
    offsetX: 0,
    offsetY: 0,
    isDragging: false,
    startX: 0,
    startY: 0,
    zoomIn() { this.scale = Math.min(this.scale + 0.25, 4); },
    zoomOut() { this.scale = Math.max(this.scale - 0.25, 0.5); if(this.scale === 0.5) { this.offsetX = 0; this.offsetY = 0; } },
    reset() { this.scale = 1; this.rotate = 0; this.offsetX = 0; this.offsetY = 0; },
    startDrag(e) {
        if (this.scale > 1) {
            this.isDragging = true;
            this.startX = e.clientX - this.offsetX;
            this.startY = e.clientY - this.offsetY;
        }
    },
    doDrag(e) {
        if (this.isDragging) {
            this.offsetX = e.clientX - this.startX;
            this.offsetY = e.clientY - this.startY;
        }
    },
    stopDrag() {
        this.isDragging = false;
    },
    toggleZoom() {
        if (this.scale > 1) this.reset();
        else this.scale = 2;
    }
}" @mousemove="doDrag($event)" @mouseup="stopDrag()" @mouseleave="stopDrag()">
    <div class="flex justify-between items-center bg-white p-2 border rounded-t-lg shadow-sm">
        <div class="flex gap-2">
            <button type="button" @click="zoomIn()" class="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-600" title="Zoom In">
                <x-heroicon-m-magnifying-glass-plus class="w-5 h-5" />
            </button>
            <button type="button" @click="zoomOut()" class="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-600" title="Zoom Out">
                <x-heroicon-m-magnifying-glass-minus class="w-5 h-5" />
            </button>
            <button type="button" @click="rotate = (rotate + 90) % 360" class="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-600" title="Rotate">
                <x-heroicon-m-arrow-path class="w-5 h-5" />
            </button>
            <button type="button" @click="reset()" class="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-600" title="Reset">
                <x-heroicon-m-arrow-uturn-left class="w-5 h-5" />
            </button>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-xs text-gray-400 italic" x-show="scale > 1">Click and drag to pan</span>
            <div class="text-xs text-gray-500 font-semibold bg-gray-100 px-2 py-1 rounded">
                Zoom: <span x-text="Math.round(scale * 100) + '%'"></span>
            </div>
        </div>
    </div>

    <div class="relative overflow-hidden bg-gray-900 rounded-b-lg flex justify-center items-center select-none" 
         style="min-height: 500px; max-height: 80vh; cursor: crosshair;">
        <div :class="{'cursor-grab': scale > 1 && !isDragging, 'cursor-grabbing': isDragging}"
             @mousedown="startDrag($event)"
             @click.dblclick="toggleZoom()"
             :style="'transform: translate(' + offsetX + 'px, ' + offsetY + 'px) scale(' + scale + ') rotate(' + rotate + 'deg); transition: ' + (isDragging ? 'none' : 'transform 0.2s ease-out') + ';'">
            <img src="{{ asset('storage/' . $record->image_path) }}" 
                 class="max-w-full h-auto shadow-2xl pointer-events-none" 
                 alt="POD Document" />
        </div>
    </div>

    @if($record->receiver_signature)
        <div class="flex flex-col items-center gap-2 p-4 border rounded-lg bg-white shadow-sm mt-2">
            <span class="text-sm font-medium text-gray-500 uppercase tracking-wider text-xs">Receiver Signature</span>
            <div class="bg-gray-50 p-2 rounded border border-dashed border-gray-300">
                <img src="{{ str_starts_with($record->receiver_signature, 'data:image') ? $record->receiver_signature : asset('storage/' . $record->receiver_signature) }}" 
                     class="max-h-24 w-auto" />
            </div>
        </div>
    @endif
</div>

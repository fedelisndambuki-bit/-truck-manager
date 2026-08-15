<?php

namespace App\Filament\Pages;

use App\Models\Tyre;
use App\Models\Truck;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use App\Models\TyreInspection;
use App\Models\TyreRepair;

use App\AI\AIService;
use App\Traits\HasIndustryAccess;

class TyreManagement extends Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Fleet Management';

    protected static ?string $title = 'Tyre Management';

    protected static string $view = 'filament.pages.tyre-management';

    public function getSmartInsights()
    {
        $tyreData = $this->getTyreData()->map(fn($t) => [
            'serial' => $t->serial_number,
            'brand' => $t->brand,
            'status' => $t->status,
            'depth' => $t->tread_depth,
            'category' => $t->tyre_category,
            'retreads' => $t->retread_count,
        ])->toJson();

        $prompt = "As a fleet management expert, analyze this tyre data and provide 3-4 bullet points of high-level smart insights. 
        Focus on: 
        1. Brand performance (if obvious patterns exist)
        2. Rotation recommendations
        3. Potential failure risks based on tread depth
        4. Cost saving tips.
        Keep it concise and professional.
        
        Data: " . $tyreData;

        return AIService::ask($prompt);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_report')
                ->label('Generate PDF Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('generatePdfReport'),
            Action::make('add_tyre')
                ->label('Add Tyre')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('serial_number')
                        ->required()
                        ->unique('tyres', 'serial_number'),
                    TextInput::make('brand')
                        ->required(),
                    TextInput::make('model'),
                    TextInput::make('size')
                        ->required(),
                    Select::make('tyre_category')
                        ->options([
                            'Steer' => 'Steer',
                            'Drive' => 'Drive',
                            'Trailer' => 'Trailer',
                        ])
                        ->required(),
                    TextInput::make('tread_depth')
                        ->numeric()
                        ->suffix('mm')
                        ->default(12),
                    TextInput::make('pressure')
                        ->numeric()
                        ->suffix('PSI'),
                    Select::make('type')
                        ->options([
                            'New' => 'New',
                            'Retread' => 'Retread',
                        ])
                        ->default('New'),
                    Select::make('status')
                        ->options([
                            'Spare' => 'Available (Spare)',
                            'Active' => 'Installed (Active)',
                            'Discarded' => 'Scrapped (Discarded)',
                            'Under Repair' => 'Under Repair',
                        ])
                        ->default('Spare'),
                    DatePicker::make('purchase_date'),
                    TextInput::make('supplier'),
                    TextInput::make('purchase_cost')
                        ->numeric()
                        ->prefix(session('currency', 'KES')),
                    TextInput::make('warehouse_location'),
                    Textarea::make('warranty_details'),
                ])
                ->action(function (array $data) {
                    Tyre::create([
                        'company_id' => auth()->user()->company_id,
                        'serial_number' => $data['serial_number'],
                        'brand' => $data['brand'],
                        'model' => $data['model'] ?? null,
                        'size' => $data['size'],
                        'tyre_category' => $data['tyre_category'],
                        'tread_depth' => $data['tread_depth'],
                        'pressure' => $data['pressure'] ?? null,
                        'type' => $data['type'],
                        'status' => $data['status'],
                        'purchase_date' => $data['purchase_date'] ?? null,
                        'supplier' => $data['supplier'] ?? null,
                        'purchase_cost' => $data['purchase_cost'] ?? null,
                        'warehouse_location' => $data['warehouse_location'] ?? null,
                        'warranty_details' => $data['warranty_details'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Tyre added successfully')
                        ->success()
                        ->send();
                }),
            Action::make('configure_truck')
                ->label('Quick Configure Truck')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->form([
                    Select::make('truck_id')
                        ->label('Select Truck')
                        ->options(fn() => Truck::where('company_id', auth()->user()->company_id)->pluck('plate_number', 'id'))
                        ->required()
                        ->searchable()
                        ->live(),
                    Select::make('axle_config')
                        ->options([
                            '2-axle' => '2 Axles (4-6 Wheels)',
                            '3-axle' => '3 Axles (6-10 Wheels)',
                            '4-axle' => '4 Axles (10-14 Wheels)',
                            '5-axle' => '5 Axles (14-18 Wheels)',
                            '6-axle' => '6 Axles (18-22 Wheels)',
                        ])
                        ->required(),
                    Select::make('tyre_count')
                        ->options([
                            4 => '4 Tyres',
                            6 => '6 Tyres',
                            10 => '10 Tyres',
                            12 => '12 Tyres',
                            14 => '14 Tyres',
                            18 => '18 Tyres',
                            22 => '22 Tyres',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $truck = Truck::find($data['truck_id']);
                    if ($truck && $truck->company_id === auth()->user()->company_id) {
                        $truck->update([
                            'axle_config' => $data['axle_config'],
                            'tyre_count' => $data['tyre_count'],
                        ]);

                        Notification::make()
                            ->title('Truck configuration updated')
                            ->success()
                            ->send();
                        
                        $this->dispatch('trucks-refreshed');
                    }
                }),
        ];
    }

    public function getTrucksForLayout(): array
    {
        return Truck::where('company_id', auth()->user()->company_id)->get()->toArray();
    }

    public function editTyreAction(): Action
    {
        return Action::make('editTyre')
            ->form([
                TextInput::make('serial_number')
                    ->required(),
                TextInput::make('brand')
                    ->required(),
                TextInput::make('model'),
                TextInput::make('size')
                    ->required(),
                Select::make('tyre_category')
                    ->options([
                        'Steer' => 'Steer',
                        'Drive' => 'Drive',
                        'Trailer' => 'Trailer',
                    ]),
                TextInput::make('tread_depth')
                    ->numeric()
                    ->suffix('mm'),
                TextInput::make('pressure')
                    ->numeric()
                    ->suffix('PSI'),
                Select::make('type')
                    ->options([
                        'New' => 'New',
                        'Retread' => 'Retread',
                    ]),
                Select::make('status')
                    ->options([
                        'Spare' => 'Available (Spare)',
                        'Active' => 'Installed (Active)',
                        'Discarded' => 'Scrapped (Discarded)',
                        'Under Repair' => 'Under Repair',
                    ]),
                DatePicker::make('purchase_date'),
                TextInput::make('supplier'),
                TextInput::make('purchase_cost')
                    ->numeric()
                    ->prefix(session('currency', 'KES')),
                TextInput::make('warehouse_location'),
                Textarea::make('warranty_details'),
            ])
            ->action(function (array $data, Tyre $record) {
                $record->update($data);

                Notification::make()
                    ->title('Tyre updated successfully')
                    ->success()
                    ->send();
            });
    }

    public function inspectTyreAction(): Action
    {
        return Action::make('inspectTyre')
            ->form([
                DatePicker::make('inspection_date')
                    ->default(now())
                    ->required(),
                TextInput::make('tread_depth')
                    ->numeric()
                    ->suffix('mm')
                    ->required(),
                TextInput::make('pressure')
                    ->numeric()
                    ->suffix('PSI'),
                TextInput::make('temperature')
                    ->numeric()
                    ->suffix('°C'),
                Textarea::make('notes'),
            ])
            ->action(function (array $data, Tyre $record) {
                TyreInspection::create([
                    'tyre_id' => $record->id,
                    'inspection_date' => $data['inspection_date'],
                    'tread_depth' => $data['tread_depth'],
                    'pressure' => $data['pressure'],
                    'temperature' => $data['temperature'],
                    'notes' => $data['notes'],
                    'technician_id' => auth()->id(),
                ]);

                $record->update([
                    'tread_depth' => $data['tread_depth'],
                    'pressure' => $data['pressure'],
                ]);

                Notification::make()
                    ->title('Inspection recorded')
                    ->success()
                    ->send();
            });
    }

    public function repairTyreAction(): Action
    {
        return Action::make('repairTyre')
            ->form([
                DatePicker::make('repair_date')
                    ->default(now())
                    ->required(),
                Select::make('repair_type')
                    ->options([
                        'Puncture' => 'Puncture Repair',
                        'Vulcanization' => 'Vulcanization',
                        'Retread' => 'Retreading',
                        'Other' => 'Other',
                    ])
                    ->required(),
                TextInput::make('cost')
                    ->numeric()
                    ->prefix(session('currency', 'KES')),
                TextInput::make('vendor'),
                Textarea::make('notes'),
            ])
            ->action(function (array $data, Tyre $record) {
                TyreRepair::create([
                    'tyre_id' => $record->id,
                    'repair_date' => $data['repair_date'],
                    'repair_type' => $data['repair_type'],
                    'cost' => $data['cost'],
                    'vendor' => $data['vendor'],
                    'notes' => $data['notes'],
                ]);

                if ($data['repair_type'] === 'Retread') {
                    $record->increment('retread_count');
                    $record->update(['type' => 'Retread', 'tread_depth' => 12]); // Reset depth after retread
                }

                Notification::make()
                    ->title('Repair recorded')
                    ->success()
                    ->send();
            });
    }

    protected function getActions(): array
    {
        return [
            $this->editTyreAction(),
            $this->inspectTyreAction(),
            $this->repairTyreAction(),
        ];
    }

    public function getTruckTyreReport()
    {
        $companyId = auth()->user()->company_id;
        return Truck::where('company_id', $companyId)->get()->map(function ($truck) {
            $tyres = $truck->tyres()->where('status', 'Active');
            $installedCount = $tyres->count();
            $totalSlots = $truck->tyre_count ?: 10;
            $lastUpdated = $tyres->latest('updated_at')->first()?->updated_at?->format('d M Y') ?? '—';
            
            return [
                'plate_number' => $truck->plate_number,
                'model' => $truck->brand . ' ' . $truck->model,
                'total_slots' => $totalSlots,
                'installed' => $installedCount,
                'empty' => max(0, $totalSlots - $installedCount),
                'fill_rate' => ($installedCount / $totalSlots) * 100,
                'last_updated' => $lastUpdated,
            ];
        });
    }

    public function generatePdfReport()
    {
        $data = [
            'title' => 'Tyre Management Report',
            'date' => now()->format('d M Y'),
            'stats' => $this->getStats(),
            'tyres' => $this->getTyreData(),
            'truckReport' => $this->getTruckTyreReport(),
            'brandReport' => $this->getBrandReport(),
        ];

        $pdf = Pdf::loadView('reports.tyre-report', $data);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'tyre-management-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function installTyre($tyreId, $truckId, $position)
    {
        $companyId = auth()->user()->company_id;
        $tyre = Tyre::find($tyreId);

        if (! $tyre || (int) $tyre->company_id !== (int) $companyId) {
            return;
        }

        if ($tyre->status !== 'Spare') {
            return;
        }

        $existing = Tyre::query()
            ->where('company_id', $companyId)
            ->where('truck_id', $truckId)
            ->where('position', $position)
            ->where('id', '!=', $tyre->id)
            ->first();

        if ($existing) {
            $existing->update([
                'truck_id' => null,
                'position' => null,
                'status' => 'Spare',
                'installation_date' => null,
            ]);
        }

        $tyre->update([
            'truck_id' => $truckId,
            'position' => $position,
            'status' => 'Active',
            'installation_date' => now(),
        ]);
    }

    public function removeTyreFromTruck(int $tyreId): void
    {
        $companyId = auth()->user()->company_id;
        $tyre = Tyre::find($tyreId);

        if (! $tyre || (int) $tyre->company_id !== (int) $companyId) {
            return;
        }

        $tyre->update([
            'truck_id' => null,
            'position' => null,
            'status' => 'Spare',
            'installation_date' => null,
            'removal_date' => now(),
        ]);
    }

    public function discardTyre(int $tyreId): void
    {
        $companyId = auth()->user()->company_id;
        $tyre = Tyre::find($tyreId);

        if (! $tyre || (int) $tyre->company_id !== (int) $companyId) {
            return;
        }

        $tyre->update([
            'truck_id' => null,
            'position' => null,
            'status' => 'Discarded',
            'installation_date' => null,
            'removal_date' => now(),
        ]);
    }

    public function reinstallTyre(int $tyreId): void
    {
        $companyId = auth()->user()->company_id;
        $tyre = Tyre::find($tyreId);

        if (! $tyre || (int) $tyre->company_id !== (int) $companyId) {
            return;
        }

        $tyre->update([
            'status' => 'Spare',
            'removal_date' => null,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTyresForLayout(): array
    {
        return Tyre::query()
            ->where('company_id', auth()->user()->company_id)
            ->with('truck')
            ->get()
            ->map(function (Tyre $t) {
                $row = $t->toArray();
                $row['truck'] = $t->truck?->only(['id', 'plate_number', 'brand', 'model']);

                return $row;
            })
            ->values()
            ->all();
    }

    public function getTyreData()
    {
        return Tyre::where('company_id', auth()->user()->company_id)
            ->with('truck')
            ->get();
    }

    public function getTrucks()
    {
        return Truck::where('company_id', auth()->user()->company_id)->get();
    }

    public function getStats()
    {
        $companyId = auth()->user()->company_id;
        return [
            'total' => Tyre::where('company_id', $companyId)->count(),
            'available' => Tyre::where('company_id', $companyId)->where('status', 'Spare')->count(),
            'installed' => Tyre::where('company_id', $companyId)->where('status', 'Active')->count(),
            'retread' => Tyre::where('company_id', $companyId)->where('type', 'Retread')->count(),
            'scrapped' => Tyre::where('company_id', $companyId)->where('status', 'Discarded')->count(),
        ];
    }

    public function getHistory()
    {
        return $this->getTyreData()->filter(fn($t) => $t->truck_id !== null);
    }

    public function getInspections()
    {
        return TyreInspection::whereHas('tyre', function ($query) {
            $query->where('company_id', auth()->user()->company_id);
        })
        ->with(['tyre', 'technician'])
        ->latest('inspection_date')
        ->get();
    }

    public function getRepairs()
    {
        return TyreRepair::whereHas('tyre', function ($query) {
            $query->where('company_id', auth()->user()->company_id);
        })
        ->with('tyre')
        ->latest('repair_date')
        ->get();
    }

    public function getBrandReport()
    {
        return Tyre::where('company_id', auth()->user()->company_id)
            ->select('brand', DB::raw('count(*) as count'))
            ->groupBy('brand')
            ->get();
    }
}

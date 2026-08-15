<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource;
use App\Models\LabSample;
use App\Services\Quarry\TraceabilityService;
use Filament\Actions;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewLabSample extends ViewRecord
{
    protected static string $resource = LabSampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Lab Sample')->tabs([
                Tab::make('Overview')->schema([
                    Section::make()->schema([
                        TextEntry::make('sample_number')->label('Sample #'),
                        TextEntry::make('sample_type')->badge(),
                        TextEntry::make('material.name')->label('Product')->placeholder('—'),
                        TextEntry::make('product_grade')->placeholder('—'),
                        TextEntry::make('extractionSite.name')->label('Quarry')->placeholder('—'),
                        TextEntry::make('crushingPlant.name')->label('Plant')->placeholder('—'),
                        TextEntry::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => LabSampleResource::getStatusColor($state)),
                    ])->columns(4),
                ]),

                Tab::make('Collection')->schema([
                    Section::make()->schema([
                        TextEntry::make('batch_number')->label('Batch Number')->placeholder('—'),
                        TextEntry::make('production_number')->label('Production Number')->placeholder('—'),
                        TextEntry::make('blast_number')->label('Blast Number')->placeholder('—'),
                        TextEntry::make('collectedBy.full_name')->label('Collected By')->placeholder('—'),
                        TextEntry::make('collection_date')->date('d M Y')->placeholder('—'),
                        TextEntry::make('collection_time')->label('Collection Time')->placeholder('—'),
                        TextEntry::make('collection_location')->label('Location')->placeholder('—'),
                        TextEntry::make('gps_lat')->label('GPS Latitude')->placeholder('—'),
                        TextEntry::make('gps_lng')->label('GPS Longitude')->placeholder('—'),
                        TextEntry::make('samplingRequest.request_number')->label('Sampling Request')->placeholder('—'),
                    ])->columns(3),
                ]),

                Tab::make('Tests')->schema([
                    RepeatableEntry::make('tests')->hiddenLabel()->schema([
                        TextEntry::make('test_number')->label('Test #'),
                        TextEntry::make('testMethod.method_name')->label('Method')->placeholder('—'),
                        TextEntry::make('technician.full_name')->label('Technician')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('result')->badge()->color(fn (?string $state) => \App\Filament\Clusters\LaboratoryQualityControl\Resources\LabTestResource::getResultColor($state))->placeholder('—'),
                    ])->columns(5)->columnSpanFull(),
                ]),

                Tab::make('Approval')->schema([
                    Section::make()->schema([
                        TextEntry::make('isApprovalPending')->label('Approval Pending')
                            ->state(fn (LabSample $record) => $record->isApprovalPending() ? 'Yes' : 'No'),
                        TextEntry::make('isApproved')->label('Approved')
                            ->state(fn (LabSample $record) => $record->isApproved() ? 'Yes' : 'No'),
                    ])->columns(2),
                    RepeatableEntry::make('approvalRequests')->label('Approval History')->schema([
                        TextEntry::make('reference_number'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('submitted_at')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Certificates')->schema([
                    RepeatableEntry::make('certificates')->hiddenLabel()->schema([
                        TextEntry::make('certificate_number')->label('Certificate #'),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('customer.company_name')->label('Customer')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Traceability')->schema([
                    RepeatableEntry::make('trace')
                        ->hiddenLabel()
                        ->state(fn (LabSample $record) => app(TraceabilityService::class)->traceSample($record))
                        ->schema([
                            TextEntry::make('stage')->label('Stage')->weight('bold'),
                            IconEntry::make('found')->label('Recorded')->boolean(),
                            TextEntry::make('label')->label('Detail'),
                        ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Documents')->schema([
                    RepeatableEntry::make('documents')->hiddenLabel()->schema([
                        TextEntry::make('category')->badge(),
                        TextEntry::make('file_path')->label('File')->formatStateUsing(fn ($state) => $state ? basename($state) : '—'),
                    ])->columns(2)->columnSpanFull(),
                ]),

                Tab::make('History')->schema([
                    Section::make()->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => LabSampleResource::getStatusColor($state)),
                    ])->columns(3),
                    RepeatableEntry::make('nonConformances')->label('Non-Conformances')->schema([
                        TextEntry::make('ncr_number')->label('NCR #'),
                        TextEntry::make('severity')->badge(),
                        TextEntry::make('status')->badge(),
                    ])->columns(3)->columnSpanFull(),
                    RepeatableEntry::make('qualityHolds')->label('Quality Holds')->schema([
                        TextEntry::make('hold_number')->label('Hold #'),
                        TextEntry::make('reason')->badge(),
                        TextEntry::make('status')->badge(),
                    ])->columns(3)->columnSpanFull(),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}

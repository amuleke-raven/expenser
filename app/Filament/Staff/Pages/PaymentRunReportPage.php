<?php

namespace App\Filament\Staff\Pages;

use App\Enums\PaymentMethodType;
use App\Exports\PaymentRunExport;
use App\Models\Currency;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;

class PaymentRunReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Payment Run Report';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Payment Run Report';

    protected string $view = 'filament.staff.pages.payment-run-report-page';

    public ?array $filters = [
        'date_from' => null,
        'date_to' => null,
        'project_id' => null,
        'currency_id' => null,
        'payment_method_type' => null,
        'include_expenses' => true,
        'include_rewards' => true,
        'status' => 'approved',
    ];

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_finance');
    }

    protected function getFormSchema(): array
    {
        return [

            Section::make('Filters')
                ->extraAttributes(['class' => 'mb-3'])
                ->afterHeader([
                    Action::make('Export to Excel')
                        ->icon(Heroicon::DocumentArrowDown)
                        ->action(function () {
                            return Excel::download(
                                new PaymentRunExport($this->filters),
                                'payment-run-'.now()->format('Y-m-d').'.xlsx'
                            );
                        }),
                ])
                ->schema([
                    DatePicker::make('filters.date_from')->label('Date From'),
                    DatePicker::make('filters.date_to')->label('Date To'),
                    Select::make('filters.project_id')
                        ->label('Project')
                        ->options(Project::query()->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                    Select::make('filters.currency_id')
                        ->label('Currency')
                        ->options(Currency::query()->pluck('code', 'id'))
                        ->searchable()
                        ->nullable(),
                    Select::make('filters.payment_method_type')
                        ->label('Payment Method Type')
                        ->options(collect(PaymentMethodType::cases())->mapWithKeys(
                            fn ($case) => [$case->value => $case->label()]
                        ))
                        ->nullable(),
                    Select::make('filters.status')
                        ->label('Status')
                        ->options(['approved' => 'Approved', 'paid' => 'Paid'])
                        ->default('approved'),
                    Toggle::make('filters.include_expenses')->label('Include Expenses')->default(true),
                    Toggle::make('filters.include_rewards')->label('Include Disbursements')->default(true),
                ])->columns(2),

        ];
    }
}

<?php

namespace App\Filament\Admin\Pages;

use App\Enums\PaymentMethodType;
use App\Exports\PaymentRunExport;
use App\Models\Currency;
use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class PaymentRunReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Payment Run Report';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.admin.pages.payment-run-report-page';

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
        return auth()->user()->hasAnyRole(['accountant', 'super_admin']);
    }

    protected function getFormSchema(): array
    {
        return [
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
            Toggle::make('filters.include_expenses')->label('Include Expenses')->default(true),
            Toggle::make('filters.include_rewards')->label('Include Rewards')->default(true),
            Select::make('filters.status')
                ->label('Status')
                ->options(['approved' => 'Approved', 'paid' => 'Paid'])
                ->default('approved'),
        ];
    }

    public function export()
    {
        return Excel::download(
            new PaymentRunExport($this->filters),
            'payment-run-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}

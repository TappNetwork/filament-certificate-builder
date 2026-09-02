<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates;

use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\Pages\CreateCertificateTemplate;
use Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\Pages\EditCertificateTemplate;
use Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\Pages\ListCertificateTemplates;
use Tapp\FilamentCertificateBuilder\Models\CertificateTemplate;
use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;
use UnitEnum;

class CertificateTemplateResource extends Resource
{
    protected static ?string $model = CertificateTemplate::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $navigationLabel = 'Certificate Templates';

    protected static ?string $modelLabel = 'Certificate Template';

    protected static ?string $pluralModelLabel = 'Certificate Templates';

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        $group = config('certificate-builder.navigation_group');

        return is_string($group) || $group instanceof UnitEnum ? $group : 'Certificates';
    }

    public static function getNavigationSort(): ?int
    {
        $sort = config('certificate-builder.navigation_sort');

        return is_int($sort) ? $sort : 55;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('token_set')
                            ->label('Token set')
                            ->options(fn (): array => CertificateLayout::tokenSetOptions())
                            ->default(fn (): string => CertificateLayout::defaultTokenSet())
                            ->required()
                            ->visible(fn (): bool => count(config('certificate-builder.token_sets', [])) > 1)
                            ->dehydrated(),
                    ]),
                Section::make('Logos')
                    ->description('Upload up to 3 logos and position them freely in the layout designer.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo_1')
                            ->label('Logo 1')
                            ->collection('logo_1')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120),
                        SpatieMediaLibraryFileUpload::make('logo_2')
                            ->label('Logo 2')
                            ->collection('logo_2')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120),
                        SpatieMediaLibraryFileUpload::make('logo_3')
                            ->label('Logo 3')
                            ->collection('logo_3')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120),
                    ])
                    ->columns(3),
                Section::make('Signatures')
                    ->description('Upload signature images. Each is shown with its signature line, name, and title in the designer.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('signature_1')
                            ->label('Signature 1 image')
                            ->collection('signature_1')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120),
                        SpatieMediaLibraryFileUpload::make('signature_2')
                            ->label('Signature 2 image')
                            ->collection('signature_2')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120),
                        SpatieMediaLibraryFileUpload::make('signature_3')
                            ->label('Signature 3 image')
                            ->collection('signature_3')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('token_set')
                    ->label('Token set')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificateTemplates::route('/'),
            'create' => CreateCertificateTemplate::route('/create'),
            'edit' => EditCertificateTemplate::route('/{record}/edit'),
        ];
    }
}

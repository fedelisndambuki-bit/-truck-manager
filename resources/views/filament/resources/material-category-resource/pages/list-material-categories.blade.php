@php use App\Filament\Resources\MaterialCategoryResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />',
        'title' => 'Categories',
        'subtitle' => 'Material categories and subcategories',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Category List</div>
            <div class="table-results">
                @php $records = $this->getTable()->getRecords(); @endphp
                @if ($records->firstItem() && $records->lastItem() && $records->total())
                    Showing <strong>{{ $records->firstItem() }}-{{ $records->lastItem() }}</strong> of <strong>{{ $records->total() }}</strong> results
                @else
                    No results
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr><th>Name</th><th>Parent Category</th><th>Subcategories</th><th>Materials</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($records as $category)
                    <tr>
                        <td><a class="row-title" href="{{ MaterialCategoryResource::getUrl('edit', ['record' => $category->id]) }}">{{ $category->name }}</a></td>
                        <td>{{ $category->parent?->name ?? '— (top-level)' }}</td>
                        <td>{{ $category->subcategories()->count() }}</td>
                        <td>{{ $category->materials()->count() }}</td>
                        <td>
                            <div class="actions">
                                <a class="action-btn edit" title="Edit" href="{{ MaterialCategoryResource::getUrl('edit', ['record' => $category->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-row">No categories found</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($records->hasPages())
            <div style="padding:20px 25px;border-top:1px solid #f3f4f6;">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>

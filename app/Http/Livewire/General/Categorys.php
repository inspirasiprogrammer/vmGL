<?php

namespace App\Http\Livewire\General;

use App\Exports\General\CategoryExport;
use App\Http\Livewire\DataTable\WithBulkActions;
use App\Http\Livewire\DataTable\WithCachedRows;
use App\Http\Livewire\DataTable\WithPerPagePagination;
use App\Http\Livewire\DataTable\WithSorting;
use App\Models\General\Category;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class Categorys extends Component
{
    use WithPerPagePagination, WithSorting, WithBulkActions, WithCachedRows;

    public $code, $name, $category_id;
    
    public $showDeleteModal = false;
    public $showEditModal = false;
    public $showFilters = false;
    public $confirmingDeletion = false;
    public Category $editing;
    protected $queryString = ['sorts'];
    public $filters = [
        'search' => '',
        'code' => '',
        'name' => '',
    ];

    protected $listeners = ['refreshTransactions' => '$refresh'];

    public function rules() { 
        return [ 
            'editing.code' => 'required|min:3',
            'editing.name' => 'required',
        ]; 
    }

    public function mount() { $this->editing = $this->makeBlankTransaction(); }
    public function updatedFilters() { $this->resetPage(); }

    public function toggleShowFilters()
    {
        $this->useCachedRows();

        $this->showFilters = ! $this->showFilters;
    }

    public function edit(Category $generalcategory)
    {
        $this->useCachedRows();

        if ($this->editing->isNot($generalcategory)) $this->editing = $generalcategory;
        $this->category_id = $generalcategory->id;
        $this->showEditModal = true;
    }

    public function save()
    {
        $this->validate();
        try {
            $this->editing->save();
            $this->showEditModal = false;
            $this->dispatchBrowserEvent('alert',[
                'type'=>'success',
                'message'=>"Category Created Successfully!!"
            ]);
        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('alert',[
                'type'=>'error',
                'message'=>"Something goes wrong while creating category!!"
            ]);
        }
    }

    public function resetFilters() { $this->reset('filters'); }

    public function confirmDeletio($id)
    {
        $this->confirmingDeletion = $id;
    }

    public function deleteSelected()
    {
        $deleteCount = $this->selectedRowsQuery->count();

        $this->selectedRowsQuery->delete();

        $this->showDeleteModal = false;
        $this->confirmingDeletion = false;
        $this->notify('You\'ve deleted '.$deleteCount.' transactions');
    }

    public function makeBlankTransaction()
    {
        return Category::make(['bank_id' => 0, 'status' => 0]);
    }

    public function getRowsQueryProperty()
    {
        $query = Category::query()
            ->when($this->filters['code'], fn($query, $code) => $query->where('code', 'like', '%'.$code.'%'))
            ->when($this->filters['name'], fn($query, $name) => $query->where('name', 'like', '%'.$name.'%'))
            ->when($this->filters['search'], fn($query, $search) => $query->where('name', 'like', '%'.$search.'%'));

        return $this->applySorting($query);
    }

    public function getRowsProperty()
    {
        return $this->cache(function () {
            return $this->applyPagination($this->rowsQuery);
        });
    }

    public function create()
    {
        $this->useCachedRows();

        if ($this->editing->getKey()) $this->editing = $this->makeBlankTransaction();
        $this->category_id = '';
        $this->showEditModal = true;
    }

    public function closeModal()
    {
        $this->showEditModal = false;
    }

    public function delete(Category $generalcategory)
    {
        $generalcategory->delete();
        $this->confirmingDeletion = false;
        session()->flash('message', 'Bank Deleted Successfully');
    }

    public function confirmingDeletion( $id) 
    {
        $this->confirmingDeletion = $id;
    }

    public function render()
    {
        return view('livewire.general.categorys', [
            'categorys' => $this->rows,
        ]);
    }

    public function downloadExcel()
    {
        return Excel::download(new CategoryExport,'Category.xlsx');
    }
    
    public function downloadPDF()
    {
        $results = Category::all();
        $title = 'Daftar Kategori';
        $params = [
            'settings' => setSetting(),
            'title'  => $title,
            'results' => $results,
        ];
        $pdf = PDF::loadView('reports.generals.categorys.pdf', $params)->output();
        return response()->streamDownload(
            fn () => print($pdf),
            $title.'.pdf'
        );
    }
}
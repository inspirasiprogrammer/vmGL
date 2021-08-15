<?php

namespace App\Http\Livewire\Accounting\Banks;

use App\Exports\Accounting\BankExport;
use App\Http\Livewire\DataTable\WithBulkActions;
use App\Http\Livewire\DataTable\WithCachedRows;
use App\Http\Livewire\DataTable\WithPerPagePagination;
use App\Http\Livewire\DataTable\WithSorting;
use App\Models\Accounting\Bank as AccountingBank;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class Bank extends Component
{
    use WithPerPagePagination, WithSorting, WithBulkActions, WithCachedRows;

    public $code, $name, $address, $other_address, $phone_no, $swift_code, $account_id, $bank_id;
    
    public $showDeleteModal = false;
    public $showEditModal = false;
    public $showFilters = false;
    public $confirmingDeletion = false;
    public AccountingBank $editing;
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
            'editing.address' => 'nullable',
            'editing.other_address' => 'nullable',
            'editing.phone_no' => 'nullable',
            'editing.swift_code' => 'nullable',
        ]; 
    }

    public function mount() { $this->editing = $this->makeBlankTransaction(); }
    public function updatedFilters() { $this->resetPage(); }

    public function toggleShowFilters()
    {
        $this->useCachedRows();

        $this->showFilters = ! $this->showFilters;
    }

    public function edit(AccountingBank $accountingbank)
    {
        $this->useCachedRows();

        if ($this->editing->isNot($accountingbank)) $this->editing = $accountingbank;
        $this->bank_id = $accountingbank->id;
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
                'message'=>"Bank Created Successfully!!"
            ]);
        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('alert',[
                'type'=>'error',
                'message'=>"Something goes wrong while creating bank!!"
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
        return AccountingBank::make(['bank_id' => 0, 'status' => 0]);
    }

    public function getRowsQueryProperty()
    {
        $query = AccountingBank::query()
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
        $this->bank_id = '';
        $this->showEditModal = true;
    }

    public function closeModal()
    {
        $this->showEditModal = false;
    }

    public function delete(AccountingBank $accountingbank)
    {
        $accountingbank->delete();
        $this->confirmingDeletion = false;
        session()->flash('message', 'Bank Deleted Successfully');
    }

    public function confirmingDeletion( $id) 
    {
        $this->confirmingDeletion = $id;
    }

    public function render()
    {
        return view('livewire.accounting.banks.bank', [
            'banks' => $this->rows,
        ]);
    }

    public function downloadExcel()
    {
        return Excel::download(new BankExport,'Bank List.xlsx');
    }

    public function downloadPDF()
    {
        $results = AccountingBank::all();
        $title = 'Daftar Bank';
        $params = [
            'settings' => setSetting(),
            'title'  => $title,
            'results' => $results,
        ];
        $pdf = PDF::loadView('reports.accountings.banks.pdf', $params)->output();
        return response()->streamDownload(
            fn () => print($pdf),
            $title.'.pdf'
        );
    }
}

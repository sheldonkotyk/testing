@extends('admin.default')

@section('content')

@if (Session::get('message'))
    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif


<form method="post" action="" accept-charset="utf-8" data-search data-grid="main">
    <select name="column" class="input-medium">
        @foreach ($columns as $index=>$column)
            <option value="{!! $column !!}">{!! $column_labels[$index] !!}</option>
        @endforeach
       
    </select>
    <input name="filter" type="text" placeholder="Filter All">
    <button>Add Filter</button>
</form>


<table  class="table table-striped" data-grid="main" data-source="{!! URL::to('admin/get_entity_data') !!}/{!! $program_id !!}" table-striped>
    <thead>
        <tr>
          
           @foreach ($columns as $index=>$column)
            <th data-sort="{!! $column !!}" data-grid="main" class="sortable"> {!! $column_labels[$index] !!}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr data-template>

            @foreach ($columns as $index=>$column)
                
                <td>[[ {!!$column!!} ]]</td>
            
            @endforeach
                <td><a href="{!! URL::to('admin/edit_entity') !!}/[[hysmanage]]" class="btn btn-default btn-xs">Edit</a></td>

                
        </tr>
    </tbody>
</table>

<ul class="pagination" data-grid="main">
    <li data-template data-if-infinite data-page="[[ page ]]">Load More</li>
    <li data-template data-if-throttle data-throttle>[[ label ]]</li>
    <li data-template data-page="[[ page ]]">[[ pageStart ]] - [[ pageLimit ]]</li>
</ul>

<ul class="applied" data-grid="main">
    <li data-template>
        [? if column == undefined ?]
            [[ valueLabel ]]
        [? else ?]
            [[ valueLabel ]] in [[ columnLabel ]]
        [? endif ?]
    </li>
</ul>

<script>
    $(function()
    {
        $.datagrid('main', '.table', '.pagination', '.applied');
    });
</script>


@stop
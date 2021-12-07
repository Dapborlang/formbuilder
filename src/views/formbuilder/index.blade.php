<!--Formbuilder created by RDMarwein -->
@extends('layouts.app')
@section('script')
@endsection
@section('content')
<div class="container-fluid">
	@if(session()->has('message'))
	    <div class="alert alert-success">
	        {{ session()->get('message') }}
	    </div>
	@endif
	@if(session()->has('fail-message'))
	    <div class="alert alert-danger">
	        {{ session()->get('fail-message') }}
	    </div>
	@endif
	<div class="card">
		<div class="card-header bg-info">{{$formMaster->header}}</div>
		<div class="card-body">	
			<div style="width:100%; height: 450px; overflow:auto;">
			<table class="table table-hover">
				<tr>
					<th>Sl No.</th>
					@foreach($columns as $item)
						@if(!in_array($item,$exclude))
						@php
							$title=ucwords(str_replace('_',' ',$item));
						@endphp
							<th>{{$title}}</th>
						@endif
					@endforeach
					<th>Option</th>
				</tr>
				@foreach($model as $item1)
				<tr>
					<td>{{$loop->iteration}}</td>
					@foreach($columns as $item)
						@if(!in_array($item,$exclude))	
							@if(array_key_exists($item, $select))
								@php 
									$val=$select[$item][0];
									$val=array_values(array_slice((explode('\\',$val)), -1))[0];;
									$detail=explode("()", $select[$item][1]);
									
									if(sizeof($detail)>1)
									{
										$data=$detail[0];
										$det=$item1-> $val-> $data();
									}
									else
									{
										$data=$detail[0];
										if(isset($item1-> $val-> $data))
										{
											$det=$item1-> $val-> $data;
										}
										else
										{
											$det="please map the relationship";
										}
									}
									
								@endphp		
								<td>{{$det}}</td>
							@else
							  <td>{{$item1->$item}}</td>
							@endif
						@endif
					@endforeach
					<td>
					  <form method="POST" action="{{ url('/') }}/{{$formMaster->route}}/{{$formMaster->id}}/{{$item1->id}}">
							@method('DELETE')
							@csrf
							{!!$hola->holo($item1->id)!!}
							@if(Auth::user()->formRole->first->update)
							<a class="btn btn-info" href="{{ url('/') }}/formgen/edit/{{$formMaster->id}}/{{$item1->id}}">Edit</a>
					    @endif
					    @if(Auth::user()->formRole->first->delete)
							  <button class="btn btn-danger">Delete</button>
							@endif
						</form>
					</td>
				</tr>
				@endforeach
			</table>	
			</div>		
		</div>
	</div>
</div>
@endsection
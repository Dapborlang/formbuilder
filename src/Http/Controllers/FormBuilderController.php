<?php

namespace Rdmarwein\Formbuilder\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Rdmarwein\Formbuilder\FormMaster;
use Auth;

class FormBuilderController extends Controller
{
    public function __construct(Request $request)
    {
        $role=FormMaster::findOrFail($request->id);
        $this->middleware('auth');
        $this->middleware('credential:'.$role->role);
    }

    public function index($id,$cid=null,Request $request)
    {
        $formMaster=FormMaster::findOrFail($request->id);
        $attribute=json_decode($formMaster->attribute, true);

        $model= $formMaster->model::orderBy('id','desc');
        if(isset($attribute['condition']['index']))
        {
            foreach($attribute['condition']['index'] as $key=> $value)
            {
                $model=$model->where($key, $value);
            }
        }
        if(isset($cid))
        {
            $model=$model->where($attribute['master_key'], $cid);
        }

        if(isset($attribute['visibility']) && $attribute['visibility'])
        {
            $model=$model->where('user_id', Auth::user()->id);
        }
        
        if(isset($formMaster->CustomField->field)){
            $columns =json_decode($formMaster->CustomField->field, true);
            $columns['customF']=true;
        }
        else
        {
            $columns = \DB::connection()->getSchemaBuilder()->getColumnListing($formMaster->table_name);
        }
        
        $foreign=json_decode($formMaster->foreign_keys, true);
        
        $select=array();
        if(sizeof((array)$foreign)>0)
        {
            foreach (array_keys($foreign) as $key) {
                $select[$foreign[$key][0]]=array($key,$foreign[$key][2]);
            }
        }

        if(isset($_GET['keyword']) && $_GET['keyword']!='')
        {
            $dataString=$_GET['keyword'];
            foreach($columns as $data)
            {
                $model=$model->orWhere($data,'ilike','%'.$dataString.'%');
            }
            if(sizeof((array)$foreign)>0)
            {
                foreach (array_keys($foreign) as $key) {
                    $param=$foreign[$key][2];
                    $fModel= explode('\\',$key);
                    $fModel=end($fModel);
                    $model=$model->orWhereHas($fModel, function ($query) use($param,$dataString) {
                        $query->where($param,'ilike','%'.$dataString.'%');
                    });                
                }
            }
        }
        $model=$model->paginate(30);
        
        $exclude=json_decode($formMaster->exclude, true);
        
        $customURI=[];
        if(isset($attribute['customURI']))
        {
            $customURI=new $attribute['customURI'];
        }
       if($cid!=null && $formMaster->view=="formbuilder::formajax")
       {
        return view('formbuilder::formbuilder.index',compact('columns','formMaster','select','exclude','model','attribute','customURI'));
       }
        return view($formMaster->view.'.index',compact('columns','formMaster','select','exclude','model','attribute','customURI'));       
    }

    public function create($id,Request $request)
    {
        $formMaster=FormMaster::findOrFail($request->id);
        if(isset($formMaster->CustomField->field)){
            $columns =json_decode($formMaster->CustomField->field, true);
            $columns['customF']=true;
        }
        else
        {
            $columns = \DB::connection()->getSchemaBuilder()->getColumnListing($formMaster->table_name);
        }
        $foreign=json_decode($formMaster->foreign_keys, true);
        $select=array();
        if(sizeof((array)$foreign)>0)
        {
            foreach (array_keys($foreign) as $key) {
                $data=$key::query();
                if(isset($foreign[$key][3]))
                {
                  foreach (array_keys($foreign[$key][3][0]) as $key1) {
                    $data=$data->whereIn($key1,$foreign[$key][3][0][$key1]);
                  }
                }
                $data=$data->get();
                $select[$foreign[$key][0]]=array($data,$foreign[$key][1],$foreign[$key][2]);
            }
        }
        $exclude=json_decode($formMaster->exclude, true);
        $attribute=json_decode($formMaster->attribute, true);
            return view($formMaster->view.'.create',compact('columns','formMaster','select','exclude','attribute'));
        
    }


    public function store(Request $request)
    {
        $formMaster=FormMaster::findOrFail($request->id);
        $attribute=json_decode($formMaster->attribute, true);
        $values=$formMaster->model;
        $data=new $values;
        $except=array('_token','_method','redirect');
        foreach ($request->all() as $key => $value) {
            if(!in_array($key, $except))
            {
                $data-> $key = $value;
            }
        }
        $data->save();
        if(isset($attribute['redirect']))
        {
            return redirect($attribute['redirect'].'?master_key='.$data->id)->with(['message'=> 'Added Successfully','data'=>$data]);
        }
        else
        {
            return redirect()->back()->with(['message'=> 'Added Successfully','data'=>$data]);
        }
    }

    public function show($id)
    {
        //
    }

    public function edit($id,$cid)
    {
        $formMaster=FormMaster::findOrFail($id);
        $model= $formMaster->model::findOrFail($cid);
        if(isset($formMaster->CustomField->field)){
            $columns =json_decode($formMaster->CustomField->field, true);
            $columns['customF']=true;
        }
        else
        {
            $columns = \DB::connection()->getSchemaBuilder()->getColumnListing($formMaster->table_name);
        }

        $foreign=json_decode($formMaster->foreign_keys, true);
        $select=array();
        if(sizeof((array)$foreign)>0)
        {
            foreach (array_keys($foreign) as $key) {
                $data=$key::all();
                $select[$foreign[$key][0]]=array($data,$foreign[$key][1],$foreign[$key][2]);
            }
        }
        $exclude=json_decode($formMaster->exclude, true);
        $attribute=json_decode($formMaster->attribute, true);
        return view($formMaster->view.'.edit',compact('columns','formMaster','select','exclude','attribute','model'));
        
    }

    public function update(Request $request, $id,$cid)
    {
        $formMaster=FormMaster::findOrFail($request->id);
        $values=$formMaster->model;
        $data=$values::findOrFail($cid);
        $except=array('_token','_method','redirect');
        foreach ($request->all() as $key => $value) {
            if(!in_array($key, $except))
            {
                $data-> $key = $value;
            }
        }
        $data->save();
        return redirect()->back()->with(['message'=> 'Added Successfully','data'=>$data]);
    }

    public function destroy($id,$cid)
    {
        try {
            $model=FormMaster::findOrFail($id);
            $values=$model->model;
            $data=$values::findOrFail($cid);
            $data->delete();
            return redirect()->back()->with('message', 'Deleted Successfully');        
         } catch ( \Exception $e) {
            return redirect()->back()->with('fail-message', 'Cannot delete or update a parent row: a foreign key constraint fails');      
         }
    }

    public function indexDetail($id,$cid)
    {
        $formMaster=FormMaster::findOrFail($id);
        $attribute=json_decode($formMaster->attribute, true);

        $model= $formMaster->model::orderBy('id','desc');
        if(isset($attribute['condition']['index']))
        {
            foreach($attribute['condition']['index'] as $key=> $value)
            {
                $model=$model->where($key, $value);
            }
        }
        $model=$model->where($_GET['column'],$cid)->get();
        
        $columns = \DB::connection()->getSchemaBuilder()->getColumnListing($formMaster->table_name);
        
        $foreign=json_decode($formMaster->foreign_keys, true);
        
        $select=array();
        if(sizeof((array)$foreign)>0)
        {
            foreach (array_keys($foreign) as $key) {
                $select[$foreign[$key][0]]=array($key,$foreign[$key][2]);
            }
        }
        
        $exclude=json_decode($formMaster->exclude, true);
        return view('formbuilder::formajax.ajax',compact('columns','formMaster','select','exclude','model'));
    }

    public function finalize($id,$tid,Request $request)
    {
        $formPopulate=FormPopulate::select('id')
        ->where('header',$request->field)
        ->first();
        $model=FormPopulate::findOrFail($formPopulate->id);
        $values='App\\'.$model->model;
        $data=$values::findOrFail($tid);
        $data->finalize=true;
        $data->save();
        if(isset($request->redirect) && $request->redirect!='')
        {
            return redirect($request->redirect)->with('data',$data);
        }
        else
        {
            return redirect()->back()->with('message', 'Updated Successfully');
        }
    }
    public function getFormField($id)
    {
        $formMaster=FormMaster::findOrFail($id);
        $field=array();
        $columns = \DB::connection()->getSchemaBuilder()->getColumnListing($formMaster->table_name);
        foreach($columns as $item)
        {
            if($item!='created_at' && $item!='updated_at')
		    {
                $field[$item]=$item;
            }			  
		 }
			
        return $field;
    }
}

<?php
namespace App\Http\Controllers\Censeur;
use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Inertia\Inertia;
class EvaluationController extends Controller { public function index(Request $request){$evaluations=Evaluation::with(['classRoom:id,name','subject:id,name','teacher.user:id,name'])->when($request->filled('status'),fn($q)=>$q->where('status',$request->string('status')))->latest('evaluation_date')->paginate(20)->withQueryString(); return Inertia::render('Admin/Evaluations/Index',['evaluations'=>$evaluations,'filters'=>$request->only('status'),'routePrefix'=>'censeur']);} }

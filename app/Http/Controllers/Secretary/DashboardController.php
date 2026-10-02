<?php
namespace App\Http\Controllers\Secretary;
use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use Inertia\Inertia;
class DashboardController extends Controller { public function __invoke(){return Inertia::render('Secretary/Dashboard',['stats'=>['total'=>Evaluation::count(),'draft'=>Evaluation::where('status','draft')->count(),'in_progress'=>Evaluation::where('status','in_progress')->count(),'validated'=>Evaluation::where('status','validated')->count()],'recentEvaluations'=>Evaluation::with(['classRoom:id,name','subject:id,name','teacher.user:id,name'])->latest('evaluation_date')->limit(8)->get()]);} }

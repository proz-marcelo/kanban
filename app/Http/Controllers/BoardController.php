<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Column;
use App\Models\Card;
use App\Models\MoveHistory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BoardController extends Controller
{
    public function index()
    {
        return Board::select('id','title','description','owner_id','created_at','updated_at')->get();
    }

    public function show($id)
    {
        $board = Board::with(['columns' => function($q){ $q->orderBy('order'); }, 'columns.cards' => function($q){ $q->orderBy('position'); }])->find($id);
        if (!$board) return response()->json(['error'=>['code'=>'not_found','message'=>'Board não encontrado']], 404);
        return $board;
    }

    public function history($id)
    {
        $exists = Board::where('id',$id)->exists();
        if (!$exists) return response()->json(['error'=>['code'=>'not_found','message'=>'Board não encontrado']], 404);

        return MoveHistory::where('board_id',$id)
            ->orderByDesc('at')
            ->limit(200)
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required','string','max:80'],
            'description' => ['nullable','string'],
        ]);

        $board = Board::create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'owner_id'    => $request->user()->id,
        ]);

        // cria colunas padrão
        $defaults = ['Backlog','Em Andamento','Concluído'];
        foreach ($defaults as $i=>$name) {
            Column::create(['board_id'=>$board->id,'name'=>$name,'order'=>$i,'wip_limit'=> $name === 'Em Andamento' ? 5 : 999]);
        }

        return response()->json($board, 201);
    }

    public function update($id, Request $request)
    {
        $board = Board::find($id);
        if (!$board) return response()->json(['error'=>['code'=>'not_found','message'=>'Board não encontrado']], 404);

        $data = $request->validate([
            'title'       => ['sometimes','string','max:80'],
            'description' => ['sometimes','nullable','string'],
        ]);

        $board->fill($data)->save();
        return $board;
    }

    public function destroy($id)
    {
        $board = Board::find($id);
        if (!$board) return response()->json(['error'=>['code'=>'not_found','message'=>'Board não encontrado']], 404);
        $board->delete();
        return response()->json(['ok'=>true]);
    }
}

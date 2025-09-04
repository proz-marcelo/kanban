<?php

namespace App\Http\Controllers;

use App\Models\Column;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    public function store($boardId, Request $request)
    {
        $data = $request->validate([
            'name'      => ['required','string','max:40'],
            'order'     => ['nullable','integer'],
            'wip_limit' => ['nullable','integer','min:1'],
        ]);

        $col = Column::create([
            'board_id'  => $boardId,
            'name'      => $data['name'],
            'order'     => $data['order'] ?? 0,
            'wip_limit' => $data['wip_limit'] ?? 999,
        ]);

        return response()->json($col, 201);
    }

    public function update($id, Request $request)
    {
        $col = Column::find($id);
        if (!$col) return response()->json(['error'=>['code'=>'not_found','message'=>'Coluna não encontrada']], 404);

        $data = $request->validate([
            'name'      => ['sometimes','string','max:40'],
            'order'     => ['sometimes','integer'],
            'wip_limit' => ['sometimes','integer','min:1'],
        ]);

        $col->fill($data)->save();
        return $col;
    }

    public function destroy($id)
    {
        $col = Column::find($id);
        if (!$col) return response()->json(['error'=>['code'=>'not_found','message'=>'Coluna não encontrada']], 404);
        $col->delete();
        return response()->json(['ok'=>true]);
    }
}

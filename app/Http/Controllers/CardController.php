<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Column;
use App\Models\MoveHistory;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function store($boardId, Request $request)
    {
        $data = $request->validate([
            'column_id'   => ['required','integer','exists:columns,id'],
            'title'       => ['required','string','max:120'],
            'description' => ['nullable','string'],
        ]);

        $column = Column::where('id',$data['column_id'])->where('board_id',$boardId)->first();
        if (!$column) return response()->json(['error'=>['code'=>'bad_request','message'=>'Coluna inválida para este board']], 422);

        // WIP check
        $count = Card::where('column_id', $column->id)->count();
        if ($count >= $column->wip_limit) {
            return response()->json(['error'=>['code'=>'wip_limit','message'=>'Limite WIP atingido']], 422);
        }

        $position = ($count ? Card::where('column_id',$column->id)->max('position') + 1 : 1);

        $card = Card::create([
            'board_id'    => $boardId,
            'column_id'   => $column->id,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'position'    => $position,
            'created_by'  => $request->user()->id,
        ]);

        MoveHistory::create([
            'card_id'        => $card->id,
            'board_id'       => $boardId,
            'from_column_id' => null,
            'to_column_id'   => $column->id,
            'type'           => 'created',
            'by_user_id'     => $request->user()->id,
            'at'             => now(),
        ]);

        return response()->json($card, 201);
    }

    public function update($id, Request $request)
    {
        $card = Card::find($id);
        if (!$card) return response()->json(['error'=>['code'=>'not_found','message'=>'Card não encontrado']], 404);

        $data = $request->validate([
            'title'       => ['sometimes','string','max:120'],
            'description' => ['sometimes','nullable','string'],
        ]);

        $card->fill($data)->save();

        MoveHistory::create([
            'card_id'        => $card->id,
            'board_id'       => $card->board_id,
            'from_column_id' => $card->column_id,
            'to_column_id'   => $card->column_id,
            'type'           => 'updated',
            'by_user_id'     => $request->user()->id,
            'at'             => now(),
        ]);

        return $card;
    }

    public function move($id, Request $request)
    {
        $card = Card::find($id);
        if (!$card) return response()->json(['error'=>['code'=>'not_found','message'=>'Card não encontrado']], 404);

        $data = $request->validate([
            'to_column_id' => ['required','integer','exists:columns,id'],
            'to_position'  => ['nullable','integer','min:1'],
        ]);

        $toCol = Column::where('id',$data['to_column_id'])->where('board_id',$card->board_id)->first();
        if (!$toCol) return response()->json(['error'=>['code'=>'bad_request','message'=>'Coluna inválida para este board']], 422);

        // WIP check
        $count = Card::where('column_id', $data['to_column_id'])->when($card->column_id == $data['to_column_id'], fn($q)=>$q->where('id','!=',$card->id))->count();
        if ($count >= $toCol->wip_limit) {
            return response()->json(['error'=>['code'=>'wip_limit','message'=>'Limite WIP atingido']], 422);
        }

        $fromCol = $card->column_id;

        // Ajusta posições simples: coloca no final se não vier posição
        $newPos = $data['to_position'] ?? (Card::where('column_id',$toCol->id)->max('position') + 1);

        $card->column_id = $toCol->id;
        $card->position  = $newPos;
        $card->save();

        MoveHistory::create([
            'card_id'        => $card->id,
            'board_id'       => $card->board_id,
            'from_column_id' => $fromCol,
            'to_column_id'   => $toCol->id,
            'type'           => 'moved',
            'by_user_id'     => $request->user()->id,
            'at'             => now(),
        ]);

        return $card;
    }

    public function destroy($id, Request $request)
    {
        $card = Card::find($id);
        if (!$card) return response()->json(['error'=>['code'=>'not_found','message'=>'Card não encontrado']], 404);

        $card->delete();

        MoveHistory::create([
            'card_id'        => $id,
            'board_id'       => $card->board_id,
            'from_column_id' => $card->column_id,
            'to_column_id'   => null,
            'type'           => 'deleted',
            'by_user_id'     => $request->user()->id,
            'at'             => now(),
        ]);

        return response()->json(['ok'=>true]);
    }
}

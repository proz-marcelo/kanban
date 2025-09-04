<?php

namespace App\Http\Middleware;

use App\Models\Board;
use App\Models\Column;
use App\Models\Card;
use Closure;
use Illuminate\Http\Request;

class EnsureOwner
{
    public function handle(Request $request, Closure $next, string $resourceType)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error'=>['code'=>'unauthorized','message'=>'Usuário não autenticado']], 401);
        }

        $boardId = null;

        switch ($resourceType) {
            case 'board':
                $boardId = (int)($request->route('id') ?? $request->route('board'));
                break;
            case 'column':
                $columnId = (int)($request->route('id') ?? $request->route('column'));
                $col = Column::find($columnId);
                $boardId = $col?->board_id;
                break;
            case 'card':
                $cardId = (int)($request->route('id') ?? $request->route('card'));
                $card = Card::find($cardId);
                $boardId = $card?->board_id;
                break;
            default:
                return response()->json(['error'=>['code'=>'bad_request','message'=>'Tipo de recurso desconhecido']], 400);
        }

        if (!$boardId) {
            return response()->json(['error'=>['code'=>'not_found','message'=>'Recurso não encontrado']], 404);
        }

        $board = Board::find($boardId);
        if (!$board) {
            return response()->json(['error'=>['code'=>'not_found','message'=>'Board não encontrado']], 404);
        }

        if ((int)$board->owner_id !== (int)$user->id) {
            return response()->json(['error'=>['code'=>'forbidden','message'=>'Apenas o proprietário pode realizar esta ação']], 403);
        }

        return $next($request);
    }
}

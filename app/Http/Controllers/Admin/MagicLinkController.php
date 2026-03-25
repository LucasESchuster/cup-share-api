<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminMagicLinkResource;
use App\Models\MagicLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MagicLinkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MagicLink::with('user')->latest('created_at');

        $query->when($request->status === 'used', fn ($q) => $q->whereNotNull('used_at'))
              ->when($request->status === 'expired', fn ($q) => $q->whereNull('used_at')->where('expires_at', '<', now()))
              ->when($request->status === 'pending', fn ($q) => $q->whereNull('used_at')->where('expires_at', '>=', now()));

        return AdminMagicLinkResource::collection($query->paginate(20));
    }
}

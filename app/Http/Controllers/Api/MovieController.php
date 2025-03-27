<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\MovieRepositoryInterface;
use Illuminate\Http\Request;
use App\Http\Resources\MovieResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse; // Importer JsonResponse
use Illuminate\Http\Response; // Importer Response

class MovieController extends Controller
{
    protected $movieRepository;

    public function __construct(MovieRepositoryInterface $movieRepository)
    {
        $this->movieRepository = $movieRepository;
        $this->middleware('auth:api')->except(['index', 'show']);
        // Appliquer le middleware admin pour store, update, destroy
        $this->middleware('isAdmin')->only(['store', 'update', 'destroy']);
    }

    public function index(): JsonResponse
    {
        $movies = $this->movieRepository->all();
        return response()->json(MovieResource::collection($movies));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:movies,title',
            'description' => 'required|string',
            'duration_minutes' => 'required|integer|min:1',
            'min_age' => 'required|integer|min:0',
            'genre' => 'required|string|max:100',
            'trailer_url' => 'nullable|url',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' // Validation image
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        // Gestion simple de l'upload d'image
        if ($request->hasFile('image')) {
            // Stocke dans storage/app/public/movie_images
            // N'oubliez pas de lancer `php artisan storage:link`
            $path = $request->file('image')->store('movie_images', 'public');
            $data['image_path'] = $path;
        }

        $movie = $this->movieRepository->create($data);

        return response()->json(new MovieResource($movie), Response::HTTP_CREATED);
    }

    public function show(int $id): JsonResponse // Utiliser l'ID ici car findOrFail est dans le repo
    {
        try {
            $movie = $this->movieRepository->find($id);
            return response()->json(new MovieResource($movie));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             return response()->json(['message' => 'Movie not found'], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
         $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255|unique:movies,title,' . $id,
            'description' => 'sometimes|required|string',
            'duration_minutes' => 'sometimes|required|integer|min:1',
            'min_age' => 'sometimes|required|integer|min:0',
            'genre' => 'sometimes|required|string|max:100',
            'trailer_url' => 'sometimes|nullable|url',
             'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' // Permettre de remplacer l'image
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        // Gestion de l'upload si une nouvelle image est fournie
        if ($request->hasFile('image')) {
            // Optionnel: supprimer l'ancienne image si elle existe
            // try {
            //    $oldMovie = $this->movieRepository->find($id);
            //    if ($oldMovie->image_path) {
            //        Storage::disk('public')->delete($oldMovie->image_path);
            //    }
            // } catch (\Exception $e) {} // Ignorer si non trouvé

            $path = $request->file('image')->store('movie_images', 'public');
            $data['image_path'] = $path;
        }

        $updated = $this->movieRepository->update($id, $data);

        if ($updated) {
             // Re-récupérer le modèle mis à jour pour la réponse
             $movie = $this->movieRepository->find($id);
             return response()->json(new MovieResource($movie));
        }

        // Si findOrFail a échoué dans le repo ou update a échoué
        return response()->json(['message' => 'Movie not found or update failed'], Response::HTTP_NOT_FOUND);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->movieRepository->delete($id);

        if ($deleted) {
            // Optionnel: Supprimer l'image associée
             // Storage::disk('public')->delete($movie->image_path); // Nécessite de récupérer le film avant delete()
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } else {
            return response()->json(['message' => 'Movie not found'], Response::HTTP_NOT_FOUND);
        }
    }
}
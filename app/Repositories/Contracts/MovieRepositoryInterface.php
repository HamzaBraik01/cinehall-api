<?php
namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection; // Importer Collection

interface MovieRepositoryInterface
{
    public function all(array $columns = ['*']): Collection; // Spécifier le type de retour
    public function find(int $id); // Le type de retour dépendra de votre gestion d'erreur (Model | null ou lance une exception)
    public function create(array $data): \App\Models\Movie; // Spécifier le type de retour
    public function update(int $id, array $data): bool; // Retourne succès/échec ou le modèle mis à jour
    public function delete(int $id): bool; // Retourne succès/échec
    // public function findByGenre(string $genre): Collection; // Exemple méthode spécifique
}
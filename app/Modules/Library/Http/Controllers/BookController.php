<?php

namespace App\Modules\Library\Http\Controllers;

use App\Modules\Library\Http\Requests\StoreBookRequest;
use App\Modules\Library\Http\Requests\UpdateBookRequest;
use App\Modules\Library\Http\Resources\BookResource;
use App\Modules\Library\Models\Book;
use App\Modules\Library\Repositories\BookRepository;
use App\Modules\Library\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $service,
        private readonly BookRepository $repository,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $books = $this->repository->all(app('current_school_id'));

        return BookResource::collection($books);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = $this->service->make(app('current_school_id'), $request->validated());

        return (new BookResource($book))->response()->setStatusCode(201);
    }

    public function show(int $id): BookResource
    {
        $book = Book::forSchool(app('current_school_id'))->findOrFail($id);

        return new BookResource($book);
    }

    public function update(UpdateBookRequest $request, int $id): BookResource
    {
        $book = Book::forSchool(app('current_school_id'))->findOrFail($id);

        return new BookResource($this->service->modify($book, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $book = Book::forSchool(app('current_school_id'))->findOrFail($id);
        $this->service->deactivate($book);

        return response()->json(['message' => 'Book deleted.']);
    }
}

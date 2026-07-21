<?php

namespace App\Http\Controllers\Admin\Modules\Library;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Services\BookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class BookController extends Controller
{
    public function __construct(private readonly BookService $books) {}

    public function index(): View
    {
        $books = Book::where('school_id', app('current_school_id'))
            ->where('is_trash', false)
            ->orderBy('title')
            ->get();

        return view('admin.modules.library.books.index', compact('books'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->books->make(app('current_school_id'), $this->validated($request));

        return back()->with('status', 'Book added.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $book = Book::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->books->modify($book, $this->validated($request, $book));

        return back()->with('status', 'Book updated.');
    }

    public function deactivate(int $id): RedirectResponse
    {
        $book = Book::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->books->deactivate($book);

        return back()->with('status', 'Book deactivated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Book $book = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:150'],
            'isbn' => ['nullable', 'string', 'max:30'],
            'category' => ['nullable', 'string', 'max:80'],
            'publisher' => ['nullable', 'string', 'max:120'],
            'edition' => ['nullable', 'string', 'max:50'],
            'total_copies' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        // On edit, keep available_copies consistent with any change in total.
        if ($book !== null) {
            $delta = (int) $data['total_copies'] - (int) $book->total_copies;
            $data['available_copies'] = max(0, (int) $book->available_copies + $delta);
        }

        return $data;
    }
}

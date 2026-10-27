<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;

class CategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permissions('categories');
    }

    public function index(Request $request)
    {
        $categories = Category::withCount('items')
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('category.index', compact('categories'));
    }

    public function create()
    {
        return view('category.create', ['category' => new Category()]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('success', 'Categoría creada.');
    }

    public function show(Category $category)
    {
        return redirect()->route('items.index', ['category' => $category->id]);
    }

    public function edit(Category $category)
    {
        return view('category.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('categories.index')->with('success', 'Categoría actualizada.');
    }

    public function destroy(Category $category)
    {
        return $this->deleteOrExplain($category, 'categories.index', 'No se puede eliminar: la categoría tiene elementos.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories')->ignore($category)],
            'description' => ['nullable', 'string', 'max:255'],
        ], [], ['name' => 'nombre', 'description' => 'descripción']);
    }
}

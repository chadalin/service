<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RepairCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = RepairCategory::with('parent')->orderBy('name')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parentCategories = RepairCategory::whereNull('parent_id')->get();
        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:repair_categories,id'
        ]);

        RepairCategory::create($request->all());

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно создана');
    }

    public function edit(RepairCategory $category)
    {
        $parentCategories = RepairCategory::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->get();
            
        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, RepairCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:repair_categories,id'
        ]);

        $category->update($request->all());

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно обновлена');
    }

    public function destroy(RepairCategory $category)
    {
        // Проверяем, есть ли дочерние категории
        if ($category->children()->exists()) {
            return redirect()->back()
                ->with('error', 'Нельзя удалить категорию с подкатегориями');
        }

        // Проверяем, есть ли документы в этой категории
        if ($category->documents()->exists()) {
            return redirect()->back()
                ->with('error', 'Нельзя удалить категорию с документами');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно удалена');
    }
}
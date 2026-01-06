<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow both admin and regular users to create/update items
        // For update, check if user owns the item
        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            $items = $this->route('item');
            return $this->user() && ($this->user()->isAdmin() || $items->user_id === $this->user()->id);
        }
        
        // For create, allow any authenticated user
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $itemId = $this->route('item') ? $this->route('item')->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'quantity' => ['required', 'integer', 'min:0', 'max:999999'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:items,sku,' . $itemId],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'is_active' => ['boolean'],
            'user_id' => ['sometimes', 'required_if:is_admin,true', 'exists:users,id'],
            
            // For admin assigning items to other users
            'assign_to_user' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Item name is required.',
            'description.required' => 'Item description is required.',
            'description.min' => 'Description must be at least 10 characters.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category does not exist.',
            'sku.unique' => 'This SKU is already in use.',
            'image.image' => 'The file must be an image.',
            'image.max' => 'The image must not exceed 2MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'name' => trim($this->name),
            'description' => trim($this->description),
            'is_active' => $this->has('is_active') ? filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN) : true,
            'price' => (float) str_replace(',', '', $this->price),
        ]);
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation()
    {
        // Set user_id if not provided (for regular users)
        if (!$this->has('user_id') && $this->user()) {
            $this->merge(['user_id' => $this->user()->id]);
        }
    }
}
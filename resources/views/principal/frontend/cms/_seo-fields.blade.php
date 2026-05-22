<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title mb-0"><i class="fas fa-search mr-1"></i> SEO সেটিংস</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label>মেটা শিরোনাম</label>
            <input type="text" name="meta_title" class="form-control" maxlength="255"
                   value="{{ old('meta_title', $item->meta_title) }}"
                   placeholder="খালি থাকলে পৃষ্ঠার শিরোনাম ব্যবহার হবে">
        </div>
        <div class="form-group">
            <label>মেটা বর্ণনা</label>
            <textarea name="meta_description" class="form-control" rows="2" maxlength="500">{{ old('meta_description', $item->meta_description) }}</textarea>
        </div>
        <div class="form-group">
            <label>মেটা কীওয়ার্ড</label>
            <input type="text" name="meta_keywords" class="form-control" maxlength="500"
                   value="{{ old('meta_keywords', $item->meta_keywords) }}"
                   placeholder="শিক্ষা, স্কুল, ...">
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Robots</label>
                <select name="robots" class="form-control">
                    @foreach(['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'] as $robots)
                        <option value="{{ $robots }}" {{ old('robots', $item->robots ?? 'index, follow') === $robots ? 'selected' : '' }}>{{ $robots }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>OG ছবি (সোশ্যাল শেয়ার)</label>
                <input type="file" name="og_image" class="form-control-file" accept="image/*">
                @if($item->og_image)
                    <img src="{{ asset('storage/'.$item->og_image) }}" alt="" class="img-thumbnail mt-2" style="max-height:80px">
                @endif
            </div>
        </div>
    </div>
</div>

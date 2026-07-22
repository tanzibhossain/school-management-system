@extends('layouts.admin')
@section('title', __('Navigation Menu'))
@section('content')
  @include('admin.partials.page-header', ['title' => __('Navigation menu'), 'crumbs' => [__('Website'), __('Menus')]])

  @php
    $tree = $menu->items->map(fn ($i) => [
      'label'   => $i->label,
      'type'    => $i->type,
      'page_id' => $i->page_id,
      'url'     => $i->url,
      'target'  => $i->target,
      'children' => $i->children->map(fn ($c) => [
        'label' => $c->label, 'type' => $c->type, 'page_id' => $c->page_id, 'url' => $c->url, 'target' => $c->target, 'children' => [],
      ])->all(),
    ])->all();

    $pageOptions = $pages->map(fn ($p) => ['id' => $p->id, 'title' => $p->title])->all();
  @endphp

  <form method="POST" action="{{ route('admin.menus.save') }}" id="menu-form">
    @csrf @method('PUT')
    <input type="hidden" name="items" id="menu-items-json">

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header">{{ __('Add To Menu') }}</div>
          <div class="card-body">
            <label class="form-label small">{{ __('Page') }}</label>
            <div class="input-group input-group-sm mb-3">
              <select class="form-select" id="add-page-select">
                @foreach ($pages as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach
              </select>
              <button class="btn btn-outline-primary" type="button" id="add-page-btn"><i class="bi bi-plus-lg"></i> {{ __('Add') }}</button>
            </div>

            <label class="form-label small">{{ __('Custom Link') }}</label>
            <input type="text" class="form-control form-control-sm mb-1" id="add-link-label" placeholder="{{ __('Label') }}">
            <div class="input-group input-group-sm mb-3">
              <input type="text" class="form-control" id="add-link-url" placeholder="https://…  or  /slug">
              <button class="btn btn-outline-primary" type="button" id="add-link-btn"><i class="bi bi-plus-lg"></i> {{ __('Add') }}</button>
            </div>

            <label class="form-label small">{{ __('Dropdown (Parent)') }}</label>
            <div class="input-group input-group-sm">
              <input type="text" class="form-control" id="add-dropdown-label" placeholder="{{ __('E.g. About') }}">
              <button class="btn btn-outline-primary" type="button" id="add-dropdown-btn"><i class="bi bi-plus-lg"></i> {{ __('Add') }}</button>
            </div>
            <div class="form-text mt-2">{{ __('Drag Items To Reorder. Drag An Item Onto A Dropdown To Nest It (One Level).') }}</div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ __('Menu Structure') }}</span>
            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-save"></i> {{ __('Save Menu') }}</button>
          </div>
          <div class="card-body">
            <ul class="menu-list list-unstyled mb-0" id="menu-root"></ul>
            <p class="text-muted small mb-0 mt-2" id="menu-empty">{{ __('No Items Yet — Add Pages Or Links From The Left.') }}</p>
          </div>
        </div>
      </div>
    </div>
  </form>

  @push('scripts')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
  <script>
    (function () {
      var PAGES = @json($pageOptions);
      var TREE  = @json($tree);
      var root  = document.getElementById('menu-root');

      function pageSelect(selected) {
        var opts = PAGES.map(function (p) {
          return '<option value="' + p.id + '"' + (String(p.id) === String(selected) ? ' selected' : '') + '>' + esc(p.title) + '</option>';
        }).join('');
        return opts;
      }
      function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

      function renderItem(item) {
        var li = document.createElement('li');
        li.className = 'menu-item card mb-2';
        li.dataset.type = item.type || 'external';
        li.innerHTML =
          '<div class="card-body py-2 px-3">' +
            '<div class="d-flex align-items-center gap-2">' +
              '<span class="mi-handle text-muted" style="cursor:grab" title="{{ __('Drag') }}">&#9776;</span>' +
              '<input class="mi-label form-control form-control-sm" style="max-width:200px" value="' + esc(item.label) + '">' +
              '<select class="mi-type form-select form-select-sm" style="max-width:130px">' +
                '<option value="page">{{ __('Page') }}</option>' +
                '<option value="external">{{ __('Custom link') }}</option>' +
                '<option value="dropdown">{{ __('Dropdown') }}</option>' +
              '</select>' +
              '<select class="mi-page form-select form-select-sm" style="max-width:180px">' + pageSelect(item.page_id) + '</select>' +
              '<input class="mi-url form-control form-control-sm" style="max-width:200px" placeholder="https://… or /slug" value="' + esc(item.url) + '">' +
              '<div class="form-check form-switch ms-1 mb-0"><input class="mi-target form-check-input" type="checkbox"' + (item.target === '_blank' ? ' checked' : '') + '><label class="form-check-label small">{{ __('New tab') }}</label></div>' +
              '<button type="button" class="mi-remove btn btn-sm btn-outline-danger ms-auto">&times;</button>' +
            '</div>' +
          '</div>' +
          '<ul class="menu-children list-unstyled ms-4 mt-2 mb-0"></ul>';

        li.querySelector('.mi-type').value = item.type || 'external';
        syncFields(li);

        (item.children || []).forEach(function (c) {
          li.querySelector('.menu-children').appendChild(renderItem(c));
        });
        makeSortable(li.querySelector('.menu-children'));
        return li;
      }

      function syncFields(li) {
        var type = li.querySelector('.mi-type').value;
        li.querySelector('.mi-page').style.display = type === 'page' ? '' : 'none';
        li.querySelector('.mi-url').style.display  = type === 'external' ? '' : 'none';
      }

      function makeSortable(list) {
        Sortable.create(list, {
          group: 'menu', handle: '.mi-handle', animation: 150,
          fallbackOnBody: true, invertSwap: true,
          onMove: function (evt) {
            // Enforce one level: an item with children can't drop into a children list.
            var into = evt.to.classList.contains('menu-children');
            var hasChildren = evt.dragged.querySelector('.menu-item');
            return ! (into && hasChildren);
          },
          onSort: refreshEmpty,
        });
      }

      function refreshEmpty() {
        document.getElementById('menu-empty').style.display = root.children.length ? 'none' : '';
      }

      function addItem(data) { root.appendChild(renderItem(data)); refreshEmpty(); }

      // Delegated events
      root.addEventListener('click', function (e) {
        if (e.target.classList.contains('mi-remove')) { e.target.closest('.menu-item').remove(); refreshEmpty(); }
      });
      root.addEventListener('change', function (e) {
        if (e.target.classList.contains('mi-type')) { syncFields(e.target.closest('.menu-item')); }
      });

      document.getElementById('add-page-btn').addEventListener('click', function () {
        var sel = document.getElementById('add-page-select');
        var opt = sel.options[sel.selectedIndex]; if (! opt) return;
        addItem({ label: opt.textContent, type: 'page', page_id: opt.value, target: '_self', children: [] });
      });
      document.getElementById('add-link-btn').addEventListener('click', function () {
        var label = document.getElementById('add-link-label').value.trim();
        var url = document.getElementById('add-link-url').value.trim();
        if (! label) return;
        addItem({ label: label, type: 'external', url: url, target: '_self', children: [] });
        document.getElementById('add-link-label').value = ''; document.getElementById('add-link-url').value = '';
      });
      document.getElementById('add-dropdown-btn').addEventListener('click', function () {
        var label = document.getElementById('add-dropdown-label').value.trim(); if (! label) return;
        addItem({ label: label, type: 'dropdown', target: '_self', children: [] });
        document.getElementById('add-dropdown-label').value = '';
      });

      function serialize(list) {
        return Array.prototype.slice.call(list.children)
          .filter(function (el) { return el.classList.contains('menu-item'); })
          .map(function (el) {
            var type = el.querySelector('.mi-type').value;
            var o = {
              label:  el.querySelector('.mi-label').value.trim(),
              type:   type,
              target: el.querySelector('.mi-target').checked ? '_blank' : '_self',
            };
            if (type === 'page')     o.page_id = el.querySelector('.mi-page').value;
            if (type === 'external') o.url = el.querySelector('.mi-url').value.trim();
            var childList = el.querySelector('.menu-children');
            var children = childList ? serialize(childList) : [];
            if (children.length) o.children = children;
            return o;
          })
          .filter(function (o) { return o.label; });
      }

      document.getElementById('menu-form').addEventListener('submit', function () {
        document.getElementById('menu-items-json').value = JSON.stringify(serialize(root));
      });

      // Initial render
      TREE.forEach(addItem);
      makeSortable(root);
      refreshEmpty();
    })();
  </script>
  @endpush
@endsection

<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'books' ? 'active' : '' }}" href="{{ route('admin.library.books.index') }}">Books</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'members' ? 'active' : '' }}" href="{{ route('admin.library.members.index') }}">Members</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'borrow' ? 'active' : '' }}" href="{{ route('admin.library.borrow.index') }}">Borrow / return</a></li>
</ul>

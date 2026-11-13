{{-- ARS ERP Finance Software — always-visible pagination --}}
@if ($paginator->hasPages() || true)
 <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
  {{-- Info --}}
  <p style="font-size:12px;color:var(--gray-400);margin:0;">
   @if($paginator->total() > 0)
   Showing <b style="color:var(--gray-600);">{{ $paginator->firstItem() }}</b>–<b style="color:var(--gray-600);">{{ $paginator->lastItem() }}</b> of <b style="color:var(--gray-600);">{{ $paginator->total() }}</b>
   @else
   No results
   @endif
  </p>

  {{-- Controls --}}
  <nav style="display:flex;align-items:center;gap:4px;">
   {{-- Prev --}}
   @if ($paginator->onFirstPage())
    <span style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:8px;background:var(--gray-100);color:var(--gray-400);font-size:13px;font-weight:500;cursor:not-allowed;user-select:none;">&#8249; Prev</span>
   @else
    <a href="{{ $paginator->previousPageUrl() }}" style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:8px;background:var(--gray-100);color:var(--gray-600);font-size:13px;font-weight:500;text-decoration:none;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-200)'" onmouseout="this.style.background='var(--gray-100)'">&#8249; Prev</a>
   @endif

   {{-- Page numbers --}}
   @foreach ($elements as $element)
    @if (is_string($element))
     <span style="padding:6px 6px;font-size:13px;color:var(--gray-400);">&#8230;</span>
    @endif
    @if (is_array($element))
     @foreach ($element as $page => $url)
      @if ($page == $paginator->currentPage())
       <span style="display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:6px 10px;border-radius:8px;background:linear-gradient(135deg,var(--role-grad-1,#E53935),var(--role-grad-2,#FF5252));color:#fff;font-size:13px;font-weight:700;box-shadow:0 2px 8px rgba(229,57,53,0.3);">{{ $page }}</span>
      @else
       <a href="{{ $url }}" style="display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:6px 10px;border-radius:8px;background:var(--gray-100);color:var(--gray-600);font-size:13px;font-weight:500;text-decoration:none;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-200)'" onmouseout="this.style.background='var(--gray-100)'">{{ $page }}</a>
      @endif
     @endforeach
    @endif
   @endforeach

   {{-- Next --}}
   @if ($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}" style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:8px;background:var(--gray-100);color:var(--gray-600);font-size:13px;font-weight:500;text-decoration:none;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-200)'" onmouseout="this.style.background='var(--gray-100)'">Next &#8250;</a>
   @else
    <span style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:8px;background:var(--gray-100);color:var(--gray-400);font-size:13px;font-weight:500;cursor:not-allowed;user-select:none;">Next &#8250;</span>
   @endif
  </nav>
 </div>
@endif

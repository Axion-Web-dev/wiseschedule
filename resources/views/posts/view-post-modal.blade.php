<div style="max-width: 600px;">
    <h3 style="margin-bottom: 16px; font-size: 18px; font-weight: 600;">{{ $record->title }}</h3>
    <div style="margin-bottom: 16px;">
        <strong>Status:</strong> 
        <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; background-color: {{ $record->status === 'publish' ? '#10b981' : ($record->status === 'pending' ? '#f59e0b' : '#ef4444') }}; color: white;">
            {{ ucfirst($record->status) }}
        </span>
    </div>
    <div style="line-height: 1.6; color: #6b7280;">
        <strong>Description:</strong><br>
        <div style="margin-top: 8px; padding: 12px; background-color: #f9fafb; border-radius: 6px;">
            {{ $record->content }}
        </div>
    </div>
</div>

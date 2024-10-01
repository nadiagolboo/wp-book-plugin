<div class="wrap">
    <h2>{{ __('Book Infos', 'book-plugin') }}</h2>
    <table class="widefat fixed" cellspacing="0">
        <thead>
        <tr>
            <th>{{ __('ID', 'book-plugin') }}</th>
            <th>{{ __('Post ID', 'book-plugin') }}</th>
            <th>{{ __('Post Title', 'book-plugin') }}</th>
            <th>{{ __('ISBN', 'book-plugin') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($results as $row)
            <tr>
                <td>{{ $row->ID }}</td>
                <td>{{ $row->post_id }}</td>
                <td>{{ get_the_title($row->post_id) }}</td>
                <td>{{ $row->isbn }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
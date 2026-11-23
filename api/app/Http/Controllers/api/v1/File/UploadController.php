<?php

namespace App\Http\Controllers\api\v1\File;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Models\File\Upload;
use App\Services\File\ChunkedUploadService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(protected ChunkedUploadService $uploads) {}

    /** An upload belongs to the user who started it */
    private function own(Request $request, string $id): Upload
    {
        return Upload::where('user_id', $request->user()->id)->findOrFail($id);
    }

     public function start(Request $request)
    {
        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255', 'regex:/\.mp4$/i'],
            'size'     => ['required', 'integer', 'min:12', 'max:' . config('uploads.max_bytes')],
        ]);

        $upload = $this->uploads->start($request->user(), $data['filename'], (int) $data['size']);

        return AppHelpers::jsonResponse('Upload started', 201, [
            'id' => $upload->id, 'chunk_size' => config('uploads.chunk_bytes'), 'received' => 0, 'complete' => false,
        ]);
    }

    /** Where the upload stands: used to resume after a failure or a reload */
    public function status(Request $request, string $uploadId)
    {
        $upload = $this->own($request, $uploadId);

        return AppHelpers::jsonResponse('Upload status', 200, [
            'id'         => $upload->id,
            'chunk_size' => config('uploads.chunk_bytes'),
            'received'   => $this->uploads->received($upload),
            'complete'   => $upload->status === 'complete',
        ]);
    }

    public function append(Request $request, string $uploadId)
    {
        $upload = $this->own($request, $uploadId);
        abort_unless($upload->status === 'uploading', 409, 'This upload is already complete');

        $offset = $request->query('offset');
        abort_unless(is_numeric($offset) && (int) $offset >= 0, 422, 'The offset is required');

        $chunk = $request->getContent();
        abort_if($chunk === '', 422, 'The chunk is empty');
        abort_if(strlen($chunk) > config('uploads.chunk_bytes'), 413, 'The chunk is too large');

        $result = $this->uploads->append($upload, (int) $offset, $chunk);

        if (!$result['ok']) {
            return AppHelpers::jsonResponse('The offset does not match the data received', 409, ['received' => $result['received']]);
        }

        return AppHelpers::jsonResponse('Chunk received', 200, [
            'received' => $result['received'], 'complete' => $result['complete'],
        ]);
    }

    public function cancel(Request $request, string $uploadId)
    {
        $this->uploads->discard($this->own($request, $uploadId));
        return AppHelpers::jsonResponse('Upload cancelled', 200);
    }
}

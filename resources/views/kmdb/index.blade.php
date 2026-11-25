<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KMDB - Telecom PM POC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">Telecom PM POC</a>
            <div class="navbar-nav">
                <a class="nav-link active" href="{{ route('kmdb.index') }}">KMDB & Qwen Q&amp;A</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Ask KMDB with Qwen -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white fw-semibold">Ask KMDB (Powered by Local Qwen via Ollama)</div>
            <div class="card-body">
                <form method="GET" action="{{ route('kmdb.index') }}" class="row g-2">
                    <div class="col-md-7">
                        <input type="text" name="question" value="{{ $question }}" class="form-control"
                            placeholder="e.g. What are the steps in Network Maintenance SOP?" required>
                    </div>
                    <div class="col-md-3">
                        <select name="project_id" class="form-select">
                            <option value="">All Projects</option>
                            @foreach ($projects as $proj)
                                <option value="{{ $proj->id }}" @selected((string) request('project_id') === (string) $proj->id)>
                                    {{ $proj->code }} - {{ $proj->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Ask Qwen</button>
                    </div>
                </form>

                @if ($askResult)
                    <hr>
                    @if ($askResult['ok'])
                        <div class="alert alert-info mb-2">
                            <div class="small text-muted mb-1">Model: <strong>{{ $askResult['model'] }}</strong></div>
                            <div style="white-space: pre-line;">{{ $askResult['answer'] }}</div>
                        </div>
                        @if (!empty($askResult['sources']))
                            <div class="small text-secondary">
                                <strong>Referenced Chunks:</strong>
                                @foreach ($askResult['sources'] as $src)
                                    <span class="badge bg-secondary">{{ $src['doc_code'] }} {{ $src['version'] }}</span>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="alert alert-danger mb-0">{{ $askResult['error'] }}</div>
                    @endif
                @endif
            </div>
        </div>

        <div class="row g-4">
            <!-- Upload / Add SOP or MOP -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">Add SOP / MOP / Technical Doc</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('kmdb.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required
                                    placeholder="e.g. Fiber Link Fault SOP">
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label">Type</label>
                                    <select name="doc_type" class="form-select">
                                        <option value="SOP">SOP</option>
                                        <option value="MOP">MOP</option>
                                        <option value="HLD">HLD</option>
                                        <option value="REPORT">REPORT</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Version</label>
                                    <input type="text" name="version" class="form-control" value="v1.0" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label">Doc Code (Optional)</label>
                                    <input type="text" name="doc_code" class="form-control" placeholder="SOP-NET-002">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="approved">approved</option>
                                        <option value="draft">draft</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Project (Optional)</label>
                                <select name="project_id" class="form-select">
                                    <option value="">Global / None</option>
                                    @foreach ($projects as $proj)
                                        <option value="{{ $proj->id }}">{{ $proj->code }} - {{ $proj->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Document Content (SOP / MOP Steps)</label>
                                <textarea name="content_text" rows="4" class="form-control"
                                    placeholder="Paste steps or notes here..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Or Upload Text/Markdown File (.txt, .md)</label>
                                <input type="file" name="file" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Save &amp; Index into KMDB</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Stored Documents List -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">Indexed KMDB Documents ({{ $documents->count() }})</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Title</th>
                                        <th>Type / Ver</th>
                                        <th>Status</th>
                                        <th>Chunks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($documents as $doc)
                                        <tr>
                                            <td><code>{{ $doc->doc_code }}</code></td>
                                            <td>{{ $doc->title }}</td>
                                            <td>{{ $doc->doc_type }} ({{ $doc->version }})</td>
                                            <td><span
                                                    class="badge bg-{{ $doc->status === 'approved' ? 'success' : 'secondary' }}">{{ $doc->status }}</span>
                                            </td>
                                            <td>{{ $doc->chunks_count ?? $doc->chunks->count() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-3 text-muted">No documents yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
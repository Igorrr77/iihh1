# PROCESS_EDITOR

Current editor MVP capabilities:
- drag-and-drop node placement
- auto-linking nodes with SVG edges
- zoom in/out and reset view
- local autosave (900ms debounce) in browser storage
- graph payload preview in inspector
- server-side compile/validate request buttons

Technical notes:
- pure Vanilla JS
- no frontend framework
- canvas nodes are HTML blocks
- edges are rendered through SVG cubic paths

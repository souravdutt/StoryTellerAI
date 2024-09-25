<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>AI App!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        pre {
            font-family: unset;
            text-wrap: unset;
            line-height: normal;
        }
        pre ol, pre ul {
            line-height: normal;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 0;
        }

        pre h1, pre h2, pre h3, pre h4, pre h5, pre h6, pre p {
            margin-bottom: 0.5rem;
        }

        .max-w-75 {
            max-width: 75%;
        }
    </style>
</head>
<body>
    <div class="container-fuild bg-light min-vh-100">
        <x-navbar />

        <div class="container my-2 max-w-75 mx-auto">
            <div class="card">
                <div class="output-container card-body mb-2 overflow-auto" style="height:calc(100vh - 235px);">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="conversation"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-12">
                            <form class="generate-story-form d-flex flex-column">
                                @csrf
                                <textarea class="form-control" style="resize: none" placeholder="Give a topic and AI will generate a story for you..." name="generateText" cols="30" rows="3"></textarea>
                                <button type="submit" class="btn btn-dark mt-2 ms-auto">Generate</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js" integrity="sha512-6sSYJqDreZRZGkJ3b+YfdhB3MzmuP9R7X1QZ6g5aIXhRvR1Y/N/P47jmnkENm7YL3oqsmI6AK+V6AD99uWDnIw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/showdown/2.0.0/showdown.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            document.querySelector(".generate-story-form").addEventListener("submit", function(e) {
                e.preventDefault();
                const form = this;
                const btn = form.querySelector("[type='submit']");
                const btnHtml = btn.innerHTML;

                // Disable the button during request
                form.querySelector("[name='generateText']").disabled = true;
                btn.innerHTML = "<span class='spinner-grow spinner-grow-sm'></span>";
                btn.disabled = true;

                // Reset the output container
                document.querySelector(".output-container").classList.remove("d-none");
                const conversation = document.querySelector(".conversation");
                conversation.insertAdjacentHTML("beforeend", "<div class='prompt bg-light rounded max-w-75 w-100 p-2 mb-2 border ms-auto'>" + form.querySelector("[name='generateText']").value + "</div>");
                conversation.insertAdjacentHTML("beforeend", "<pre class='prompt-response bg-light rounded max-w-75 w-100 p-2 mb-2 border'></pre>");

                const lastResponse = conversation.querySelectorAll(".prompt-response").item(conversation.querySelectorAll(".prompt-response").length - 1);
                lastResponse.insertAdjacentHTML("beforeend", "<span class='loading-response'><span class='spinner-grow spinner-grow-sm'></span></span>");

                document.querySelectorAll(".output-container .alert").forEach(function(alert) {
                    alert.remove();
                });

                let scrollTo = lastResponse.offsetTop;
                document.querySelector(".output-container").scrollTop = scrollTo;

                // Data for the request
                const data = {
                    generateText: form.querySelector("[name='generateText']").value,
                };

                // Send the POST request with fetch for SSE
                fetch('/generate-text', {
                    method: 'POST',
                    headers: {
                        'Accept': 'text/event-stream', // Expecting a text/event-stream response
                        'Content-Type': 'application/json', // Sending JSON data
                        'X-CSRF-TOKEN': "{{ csrf_token() }}", // CSRF Token for Laravel security
                    },
                    body: JSON.stringify(data), // Send form data directly as JSON
                })
                .then(function (response) {
                    if (!response.ok) {
                        document.querySelector(".conversation .loading-response")?.remove();

                        if (response.status === 422) {
                            return response.json().then(function (errors) {
                                console.log('Validation errors:', errors);
                                for (let field in errors) {
                                    const errorMessage = "<div class='alert alert-danger'>" + errors[field] + "</div>";
                                    conversation.insertAdjacentHTML("afterend", errorMessage);
                                }
                            });
                        } else {
                            return response.text().then(function (text) {
                                console.error('Error:', text);
                            });
                        }
                    }

                    return response.body;
                })
                .then(response => {
                    const reader = response.getReader();
                    const decoder = new TextDecoder("utf-8");

                    let scrolledByUser = false;
                    let prevLastLine = "";

                    // Detect user scroll in the output container
                    document.querySelector(".output-container").addEventListener("wheel", () => { scrolledByUser = true; });
                    document.querySelector(".output-container").addEventListener("mousewheel", () => { scrolledByUser = true; });
                    document.querySelector(".output-container").addEventListener("DOMMouseScroll", () => { scrolledByUser = true; });

                    function readStream() {
                        lastResponse.insertAdjacentHTML("beforeend", "<span class='loading-response'> <span class='spinner-grow spinner-grow-sm'></span></span>");

                        return reader.read().then(({ done, value }) => {
                            if (done) {
                                console.log('Stream finished.');

                                // Convert markdown to HTML
                                const converter = new showdown.Converter();
                                let text = lastResponse.textContent;
                                let html = converter.makeHtml(text);
                                lastResponse.innerHTML = html;

                                if (!scrolledByUser) {
                                    document.querySelector(".output-container").scrollTop = scrollTo + lastResponse.offsetHeight;
                                }

                                // Re-enable the buttons
                                form.querySelector("[name='generateText']").disabled = false;
                                btn.innerHTML = btnHtml;
                                btn.disabled = false;
                                document.querySelector(".conversation .loading-response")?.remove();
                                return;
                            }

                            const chunk = decoder.decode(value, { stream: true });
                            const lines = chunk.split("\n\n");

                            let firstLine = lines[0];
                            let lastLine = lines[lines.length - 1];

                            if (!firstLine.startsWith("data: ")) {
                                lines[0] = prevLastLine + firstLine;
                            }

                            try {
                                JSON.parse(lastLine.replace("data: ", ""));
                                prevLastLine = '';
                            } catch (error) {
                                prevLastLine = lastLine;
                                lines.pop();
                            }

                            lines.forEach(line => {
                                document.querySelector(".conversation .loading-response")?.remove();
                                if (line.startsWith("data: ")) {
                                    let buffer = line.replace("data: ", "");

                                    if (buffer === "[DONE]") {
                                        console.log("Stream completed");
                                        form.querySelector("[name='generateText']").value = '';
                                    } else {
                                        try {
                                            const parsedData = JSON.parse(buffer);
                                            if (parsedData.response) {
                                                lastResponse.insertAdjacentHTML("beforeend", parsedData.response);
                                            }
                                        } catch (error) {
                                            console.error("Error parsing chunk:", error);
                                        }

                                        document.querySelector(".conversation .loading-response")?.remove();

                                        if (!scrolledByUser) {
                                            document.querySelector(".output-container").scrollTop = scrollTo + lastResponse.offsetHeight;
                                        }
                                    }
                                }
                            });

                            return readStream();
                        });
                    }

                    readStream();
                })
                .catch(function (error) {
                    console.error('Error:', error);
                    form.querySelector("[name='generateText']").disabled = false;
                    btn.innerHTML = btnHtml;
                    btn.disabled = false;
                    document.querySelector(".conversation .loading-response")?.remove();
                });
            });
        });

    </script>
</body>
</html>

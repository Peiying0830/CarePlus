const Ajax = {
    async request(url, method = "GET", data = null) {
        const options = {
            method,
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        };

        if (data && method !== "GET") {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || "Request failed");
        }

        return result;
    },

    get(url) {
        return this.request(url, "GET");
    },

    post(url, data) {
        return this.request(url, "POST", data);
    },

    async submitForm(url, formData) {
        const response = await fetch(url, {
            method: "POST",
            body: formData,
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || "Form submission failed");
        }

        return result;
    }
};

window.Ajax = Ajax;

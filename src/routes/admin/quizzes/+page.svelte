<script lang="ts">
    const levels = [
        {
            id: 1,
            title: "Level 1",
            stories: [
                { id: "story1-1", title: "Maria's Promise" },
                { id: "story1-2", title: "Candice and Candies" },
                { id: "story1-3", title: "Hannah, the Honest Vendor" }
            ]
        },
        {
            id: 2,
            title: "Level 2",
            stories: [
                { id: "story2-1", title: "Hector's Health" },
                { id: "story2-2", title: "Helpful Maya" },
                { id: "story2-3", title: "Royce Choice" }
            ]
        },
        {
            id: 3,
            title: "Level 3",
            stories: [
                { id: "story3-1", title: "Tonya's Tooth" },
                { id: "story3-2", title: "Lola Gloria's Flowerpot" },
                { id: "story3-3", title: "Liloy and Lingling The Dog" }
            ]
        }
    ];
</script>

<div class="p-6 bg-white dark:bg-gray-900">
    <h1 class="text-3xl font-bold text-orange-600 mb-8">Quiz Monitoring Dashboard</h1>

    {#each levels as level}
        <div class="mb-8 bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <h2 class="text-2xl font-semibold text-lime-600 mb-4">{level.title}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {#each level.stories as story}
                    <a
                        href={`/admin/quizzes/story${level.id}/${story.id}`}
                        class="block p-4 bg-white border border-gray-200 rounded-lg hover:shadow-lg transition-all transform hover:-translate-y-1"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-800">{story.title}</h3>
                            <span class="text-orange-500">➜</span>
                        </div>
                        <p class="text-gray-600 mt-2 text-sm">View student quiz results</p>
                    </a>
                {/each}
            </div>
        </div>
    {/each}
</div>

<style>
    a {
        text-decoration: none;
    }

    a:hover {
        text-decoration: none;
    }
</style>

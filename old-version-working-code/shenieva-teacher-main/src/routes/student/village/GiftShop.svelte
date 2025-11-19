<script lang="ts">
    import { createEventDispatcher } from 'svelte';
    import { studentData } from '$lib/store/student_data';
    import { apiUrl } from '$lib/api_base';
    
    const dispatch = createEventDispatcher();
    
    export let studentTrash: number = 0;
    
    interface Gift {
        name: string;
        displayName: string;
        price: number;
        image: string;
        description: string;
    }
    
    const gifts: Gift[] = [
    { name: 'pencil', displayName: 'Pencil', price: 15, image: '/assets/Level_Walkthrough/gift/gifts/pencil.png', description: 'A useful pencil for writing' },
    { name: 'paper', displayName: 'Paper', price: 20, image: '/assets/Level_Walkthrough/gift/gifts/paper.png', description: 'Clean paper for notes' },
    { name: 'ruler', displayName: 'Ruler', price: 25, image: '/assets/Level_Walkthrough/gift/gifts/ruler.png', description: 'A ruler for drawing straight lines' },
    { name: 'glue', displayName: 'Glue', price: 35, image: '/assets/Level_Walkthrough/gift/gifts/glue.png', description: 'Glue stick for crafts' },
    { name: 'scissor', displayName: 'Scissors', price: 45, image: '/assets/Level_Walkthrough/gift/gifts/scissor.png', description: 'Safety scissors for cutting' },
    { name: 'crayons', displayName: 'Crayons', price: 55, image: '/assets/Level_Walkthrough/gift/gifts/crayons.png', description: 'Colorful crayons for art' },
    { name: 'notebook', displayName: 'Notebook', price: 70, image: '/assets/Level_Walkthrough/gift/gifts/notebook.png', description: 'A nice notebook for studying' },
    { name: 'backpag', displayName: 'Backpack', price: 100, image: '/assets/Level_Walkthrough/gift/gifts/backpag.png', description: 'A sturdy backpack for school' }
    ];
    
    let selectedGift: Gift | null = null;
    let isPurchasing = false;
    let purchaseMessage = '';
    let showMessage = false;
    
    function selectGift(gift: Gift) {
        selectedGift = gift;
    }
    
    async function purchaseGift() {
        if (!selectedGift || isPurchasing) return;
        
        const student = $studentData;
        if (!student) {
            showPurchaseMessage('Error: Student data not found', false);
            return;
        }
        
        if (studentTrash < selectedGift.price) {
            showPurchaseMessage(`Not enough trash! You need ${selectedGift.price} pieces but only have ${studentTrash}.`, false);
            return;
        }
        
        isPurchasing = true;
        
        try {
            const response = await fetch(apiUrl('purchase_gift.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    studentID: student.pk_studentID,
                    giftName: selectedGift.name,
                    giftPrice: selectedGift.price
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showPurchaseMessage(`Gift purchased! Shenievia is so happy! 🎁💝`, true);
                
                // Update student trash count
                studentData.update(s => ({
                    ...s,
                    studentColtrash: result.newTrashCount
                }));
                
                // Close the shop after a delay
                setTimeout(() => {
                    dispatch('close', { purchased: true });
                }, 2000);
            } else {
                showPurchaseMessage(result.message || 'Purchase failed', false);
            }
        } catch (error) {
            console.error('Purchase error:', error);
            showPurchaseMessage('Failed to purchase gift. Please try again.', false);
        } finally {
            isPurchasing = false;
        }
    }
    
    function showPurchaseMessage(message: string, success: boolean) {
        purchaseMessage = message;
        showMessage = true;
        
        setTimeout(() => {
            showMessage = false;
        }, 3000);
    }
    
    function closeShop() {
        dispatch('close');
    }
</script>

<div class="shop-overlay" on:click={closeShop} on:keydown={(e) => e.key === 'Escape' && closeShop()} role="button" tabindex="0" aria-label="Close shop"></div>

<div class="shop-container">
    <button class="shop-close-btn" on:click={closeShop} aria-label="Close">✕</button>
    
    <div class="shop-header">
        <h2>🎁 Gift Shop for Shenievia 🎁</h2>
        <div class="trash-counter">
            <span class="trash-icon">🗑️</span>
            <span class="trash-count">{studentTrash} Trash Collected</span>
        </div>
    </div>
    
    <div class="shop-content">
        <div class="gifts-grid">
            {#each gifts as gift}
                <div 
                    class="gift-card {selectedGift?.name === gift.name ? 'selected' : ''} {studentTrash < gift.price ? 'locked' : ''}"
                    on:click={() => selectGift(gift)}
                    on:keydown={(e) => e.key === 'Enter' && selectGift(gift)}
                    role="button"
                    tabindex="0"
                    aria-label="Select {gift.displayName}"
                >
                    <div class="gift-image">
                        <img src={gift.image} alt={gift.displayName} />
                        {#if studentTrash < gift.price}
                            <div class="lock-overlay">🔒</div>
                        {/if}
                    </div>
                    <div class="gift-info">
                        <h3 class="gift-name">{gift.displayName}</h3>
                        <p class="gift-description">{gift.description}</p>
                        <div class="gift-price">
                            <span class="price-icon">🗑️</span>
                            <span class="price-value">{gift.price}</span>
                        </div>
                    </div>
                    
                    {#if selectedGift?.name === gift.name}
                        <div class="purchase-section-inline">
                            <button 
                                class="purchase-btn {studentTrash < gift.price ? 'disabled' : ''}"
                                on:click={purchaseGift}
                                disabled={isPurchasing || studentTrash < gift.price}
                            >
                                {#if isPurchasing}
                                    Purchasing...
                                {:else if studentTrash < gift.price}
                                    Not Enough Trash
                                {:else}
                                    🎁 Buy Gift
                                {/if}
                            </button>
                        </div>
                    {/if}
                </div>
            {/each}
        </div>
    </div>
    
    {#if showMessage}
        <div class="purchase-message {purchaseMessage.includes('Successfully') ? 'success' : 'error'}">
            {purchaseMessage}
        </div>
    {/if}
</div>

<style>
    .shop-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 100;
        animation: fadeIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .shop-container {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 900px;
        max-height: 85vh;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 5px solid #f59e0b;
        border-radius: 20px;
        padding: 20px;
        z-index: 101;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: flex;
        flex-direction: column;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translate(-50%, -60%);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    }
    
    .shop-close-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: 2px solid white;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 102;
    }
    
    .shop-close-btn:hover {
        background: rgba(220, 38, 38, 1);
        transform: scale(1.1);
    }
    
    .shop-header {
        text-align: center;
        margin-bottom: 15px;
        flex-shrink: 0;
    }
    
    .shop-header h2 {
        font-size: 2rem;
        color: #92400e;
        margin: 0 0 10px 0;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .trash-counter {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 8px 20px;
        border-radius: 25px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 1.1rem;
        font-weight: bold;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }
    
    .trash-icon {
        font-size: 1.3rem;
    }
    
    .shop-content {
        display: flex;
        flex-direction: column;
        gap: 15px;
        flex: 1;
        overflow-y: auto;
        padding-right: 5px;
    }
    
    .gifts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
    }
    
    .gift-card {
        background: white;
        border: 3px solid transparent;
        border-radius: 15px;
        padding: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }
    
    .gift-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    .gift-card.selected {
        border-color: #10b981;
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }
    
    .gift-card.locked {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .gift-card.locked:hover {
        transform: none;
    }
    
    .gift-image {
        position: relative;
        width: 100%;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
    }
    
    .gift-image img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    
    .lock-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 3rem;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }
    
    .gift-info {
        text-align: center;
    }
    
    .gift-name {
        font-size: 1rem;
        font-weight: bold;
        color: #92400e;
        margin: 0 0 4px 0;
    }
    
    .gift-description {
        font-size: 0.8rem;
        color: #78350f;
        margin: 0 0 8px 0;
    }
    
    .gift-price {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        font-size: 1rem;
        font-weight: bold;
        color: #059669;
    }
    
    .purchase-section-inline {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 2px solid #10b981;
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            max-height: 0;
            padding-top: 0;
            margin-top: 0;
        }
        to {
            opacity: 1;
            max-height: 100px;
            padding-top: 15px;
            margin-top: 15px;
        }
    }
    
    .purchase-btn {
        width: 100%;
        padding: 10px 15px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 0.95rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }
    
    .purchase-btn:hover:not(.disabled) {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
    }
    
    .purchase-btn:active:not(.disabled) {
        transform: translateY(0);
    }
    
    .purchase-btn.disabled {
        background: linear-gradient(135deg, #9ca3af, #6b7280);
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .purchase-message {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        padding: 15px 30px;
        border-radius: 12px;
        font-size: 1.2rem;
        font-weight: bold;
        z-index: 103;
        animation: messageSlide 0.3s ease-out;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }
    
    @keyframes messageSlide {
        from {
            opacity: 0;
            transform: translate(-50%, 20px);
        }
        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }
    
    .purchase-message.success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    
    .purchase-message.error {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }
    
    @media (max-width: 768px) {
        .shop-container {
            width: 95%;
            padding: 15px;
            max-height: 90vh;
        }
        
        .shop-header h2 {
            font-size: 1.5rem;
        }
        
        .gifts-grid {
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
        }
        
        .gift-image {
            height: 100px;
        }
        
        .gift-name {
            font-size: 0.9rem;
        }
        
        .gift-description {
            font-size: 0.75rem;
        }
    }
</style>

import Hyperspeed from './reactbits/Hyperspeed';

/**
 * Teal and aqua light-trail road.
 * Kept in its own module so three.js and postprocessing load lazily, only when this background is shown.
 */
const oceanRoad = {
    distortion: 'turbulentDistortion',
    length: 400,
    roadWidth: 10,
    islandWidth: 2,
    lanesPerRoad: 3,
    fov: 90,
    fovSpeedUp: 150,
    speedUp: 2,
    carLightsFade: 0.4,
    totalSideLightSticks: 20,
    lightPairsPerRoadWay: 40,
    shoulderLinesWidthPercentage: 0.05,
    brokenLinesWidthPercentage: 0.1,
    brokenLinesLengthPercentage: 0.5,
    lightStickWidth: [0.12, 0.5] as [number, number],
    lightStickHeight: [1.3, 1.7] as [number, number],
    movingAwaySpeed: [60, 80] as [number, number],
    movingCloserSpeed: [-120, -160] as [number, number],
    carLightsLength: [400 * 0.03, 400 * 0.2] as [number, number],
    carLightsRadius: [0.05, 0.14] as [number, number],
    carWidthPercentage: [0.3, 0.5] as [number, number],
    carShiftX: [-0.8, 0.8] as [number, number],
    carFloorSeparation: [0, 5] as [number, number],
    colors: {
        roadColor: 0x071317,
        islandColor: 0x0a2025,
        background: 0x08171b,
        shoulderLines: 0x1c4145,
        brokenLines: 0x1c4145,
        leftCars: [0x5eead4, 0x14b8a6, 0x99f6e4],
        rightCars: [0x67e8f9, 0x0f766e, 0x2dd4bf],
        sticks: 0x5eead4,
    },
};

export default function HyperspeedBackdrop() {
    return <Hyperspeed effectOptions={oceanRoad} />;
}
